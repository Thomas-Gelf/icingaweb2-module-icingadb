<?php

namespace Icinga\Module\Icingadb;

use Icinga\Exception\MissingParameterException;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;
use Icinga\Module\Icingadb\IcingaStateObject\HostState;
use Icinga\Module\Icingadb\IcingaStateObject\HostStateVolatile;
use Icinga\Module\Icingadb\IcingaStateObject\ServiceState;
use Icinga\Module\Icingadb\IcingaStateObject\StateObject;
use Predis\Client;

class StateList
{
    protected $connection;

    protected $db;

    /** @var StateObject[] */
    protected $objects = array();

    protected $predis;

    protected $environment;

    protected $envChecksum;

    public function __construct(IcingaEnvironment $environment, Client $predis)
    {
        $this->environment = $environment;
        $this->envChecksum = sha1($environment->get('name'), true);
        $this->connection = $environment->getConnection();
        $this->db = $this->connection->getDbAdapter();
        $this->objects = array_merge(
            HostState::loadAll($this->connection, null, 'global_checksum'),
            ServiceState::loadAll($this->connection, null, 'global_checksum')
        );
        $this->predis = $predis; //$environment->getRedis();
    }

    /**
     * @param $result
     * @return StateObject|boolean
     * @throws MissingParameterException
     */
    public function processCheckResult($result)
    {
        // Hint: ->type is not always available, check this:
        if (! property_exists($result, 'type')) {
            var_dump($result);
            throw new MissingParameterException('Got no type');
        }

        $type = $result->type;
        $types = [
            'CheckResult'            => 'check_result',
            'StateChange'            => 'check_result',
            'Notification'           => null,
            'AcknowledgementSet'     => null,
            'AcknowledgementCleared' => null,
            'CommentAdded'           => 'comment',
            'CommentRemoved'         => 'comment',
            'DowntimeAdded'          => 'downtime',
            'DowntimeRemoved'        => 'downtime',
            'DowntimeTriggered'      => 'downtime',
            'DowntimeStarted'        => 'downtime',
        ];

        if (! array_key_exists($type, $types)) {
            var_dump($type);
            var_dump($result);
            throw new MissingParameterException('Type list incomplete, missing %s');
        }

        $eventProperty = $types[$type];
        $eventData = $eventProperty === null ? $result : $result->$eventProperty;

        list($host, $service) = $this->getHostServiceFromResult($result, $eventProperty);

        if ($host === null) {
            echo "Event has NO HOST\n";
            var_dump($type);
            var_dump($result);

            return false;
        }

        $key = self::createKey($host, $service);

        if ($this->hasKey($key)) {
            $object = $this->getObject($key);
        } else {
            $object = $this->createObject($host, $service, $key);
            if (! $object) {
                return false;
            }
            $this->objects[$key] = $object;
        }

        $method = 'process' . $type;
        if (method_exists($object, $method)) {
            $object->$method($eventData, $result->timestamp);
        } elseif ($method !== 'processStateChange') {
            // Hint: we completely ignore StateChange events
            printf("Skipping %s\n", $method);
        }

        if ($object instanceof HostState) {
            $object->getVolatile()->storeToRedis($this->predis);
        }

        return $object;
    }

    public function addPendingHost($checksum)
    {
        $now = time();
        $host = HostState::create([
            'global_checksum'   => $checksum,
            'last_state_change' => $now,
            'state'             => 99,
            'hard_state'        => 99,
            'attempt'           => 1,
            'state_type'        => 'hard',
            'last_update'       => $now,
        ], $this->connection);

        // Trigger severity calculation:
        $host->recalculateSeverity();

        $host->store();
        $this->objects[$checksum] = $host;
    }

    public function removeHosts($checksums)
    {
        foreach ($checksums as $checksum) {
            HostStateVolatile::removeFromRedis($this->predis, $checksum);
            unset($this->objects[$checksum]);
        }

        $this->db->delete(
            'host_state',
            $this->db->quoteInto('checksum in (?)', $checksums)
        );
    }

    protected function createObject($host, $service, $key)
    {
        if ($service === null) {
            return HostState::create([
                'global_checksum' => $key,
                'env_checksum'    => $this->envChecksum,
            ], $this->connection);
        } else {
            // return false;
            return ServiceState::create([
                'global_checksum' => $key,
                'env_checksum'    => $this->envChecksum,
            ], $this->connection);
        }
    }

    /**
     * @param $key
     * @return StateObject
     */
    protected function getObject($key)
    {
        return $this->objects[$key];
    }

    protected function hasKey($key)
    {
        return array_key_exists($key, $this->objects);
    }

    protected function getHostServiceFromResult($result, $eventProperty)
    {
        if (property_exists($result, 'host')) {
            $list = [$result->host];
        } elseif (property_exists($result->$eventProperty, 'host_name')) {
            $list = [$result->$eventProperty->host_name];
        } else {
            return [null, null];
        }

        if (property_exists($result, 'service')) {
            $list[] = $result->service;
        } elseif (property_exists($result, 'service_name')) {
            $list[] = $result->$eventProperty->service_name;
        } else {
            $list[] = null;
        }

        if (count($list) === 2 && $list[1] === '') {
            $list[1] = null;
        }

        return $list;
    }

    protected function createKey($host, $service = null)
    {
        $key = $host;
        if ($service !== null) {
            $key .= '!' . $service;
        }

        return sha1(
            $this->envChecksum . sha1($key, true),
            true
        );
    }
}
