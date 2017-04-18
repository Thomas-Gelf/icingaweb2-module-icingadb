<?php

namespace Icinga\Module\Icingadb;

use Icinga\Application\Logger;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;
use Icinga\Module\Icingadb\IcingaStateObject\StateObject;
use Icinga\Module\Director\Core\CoreApi;
use Predis\Client;

class IcingaEventHandler
{
    /** @var \Predis\Client */
    protected $redis;

    /** @var CoreApi */
    protected $api;

    /** @var IcingaDb */
    protected $ddo;

    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    protected $hasTransaction = false;

    protected $useTransactions = true;

    protected $lastSubscription = 0;

    /** @var IcingaEnvironment */
    protected $environment;

    /**
     * IcingaEventHandler constructor.
     * @param IcingaDb $ddo
     */
    public function __construct(IcingaEnvironment $environment)
    {
        $this->environment = $environment;
        $this->ddo = $environment->getConnection();
        $this->db = $this->ddo->getDbAdapter();
        $this->redis = $environment->getRedis();
    }

    public function processEvents()
    {
        $time = time();
        $cnt = 0;
        $cntEvents = 0;
        $ddo = $this->ddo;
//        $list = new StateList($ddo, $this->redis());
        $envName = $this->environment->get('name');
        $list = new StateList($this->environment, Redis::instance());
        $subscriberName = 'ddo';
        $myListName = "icinga:event:$subscriberName";

        // TODO: 0 is forever, leave loop after a few sec and enter again
        $max = 1000;
        while (true) {
            $redis = $this->redis();
            $this->subscribeMe($redis);
            // MULTI; LRANGE key 0 N-1; LTRIM N -1; EXEC;
            $responses = $redis->transaction(function ($tx) use ($myListName, $max) {
                $tx->lrange($myListName, 0, $max - 1);
                $tx->ltrim($myListName, $max, -1);
            });

            if (empty($responses[0])) {
                //Logger::info('(icingadb) No response, sleep 1');
                usleep(500000);
                continue;
            }
            // while ($res = $redis->brpop([$myListName], 1)) {
            // responses: 0 -> [], 1 -> Predis\Response\Status -> payload -> "OK"
            $size = 0;
            foreach ($responses[0] as $res) {
                $size += strlen($res);
                $cntEvents++;
                // Hint: $res = array(queuename, value)
                // $object = $list->processCheckResult(json_decode($res[1]));
                $object = $list->processCheckResult(json_decode($res));
                if ($object === false) {
                    continue;
                }

                if ($object->hasBeenModified() && $object->state !== null) {
                    // Logger::info('(icingadb) "%s" has been modified', $object->getGlobalHexChecksum());
                    $this->wantsTransaction();
                    $cnt++;
                    $object->store();
                    $this->handleStateChange($object);
                } else {
                    // Logger::debug('(icingadb) "%s" has not been modified', $object->getUniqueName());
                }

                if (($cnt >= 1000 && $newtime = time())
                    || ($cnt > 0 && (($newtime = time()) - $time > 1))
                ) {
                    $time = $newtime;
                    Logger::info(
                        '(%s) Committing %d events (%d total, %d bytes)',
                        $envName,
                        $cnt,
                        $cntEvents,
                        $size
                    );
                    $cnt = 0;
                    $size = 0;
                    $cntEvents = 0;
                    $this->closeTransaction();
                }
            }

            if ($cnt > 0) {
                $time = time();
                Logger::info(
                    '(%s) Committing %d events (%d total, %d bytes)',
                    $envName,
                    $cnt,
                    $cntEvents,
                    $size
                );
                $cnt = 0;
                $cntEvents = 0;
                $this->closeTransaction();
            } elseif ($cntEvents >= 1000) {
                Logger::info(
                    '(%s) %d events (%d bytes), nothing to commit',
                    $envName,
                    $cntEvents,
                    $size
                );
                $cntEvents = 0;
            } else {
                // Logger::info('(%s) Got %d bytes but no event and nothing to commit', $envName, $size);
            }

            $this->checkForMissingObjects($list);
        }
    }

    protected function subscribeMe(Client $redis)
    {
        if ($this->lastSubscription > (time() - 300)) {
            return;
        }

        $redis->setex('icinga:subscription:ddo', 600, json_encode(
            (object) [
                'types' => [
                    'CheckResult',
                    'StateChange',
                    'Notification',
                    'AcknowledgementSet',
                    'AcknowledgementCleared',
                    'CommentAdded',
                    'CommentRemoved',
                    'DowntimeAdded',
                    'DowntimeRemoved',
                    'DowntimeTriggered',
                ]
            ]
        ));

        $this->lastSubscription = time();
    }

    protected function handleStateChange(StateObject $object)
    {
        // partition by month? Manually or DB-based?
        // INSERT INTO host_eventhistory (object_checksum, timestamp) VALUES ()
        // duration -> null
    }

    protected function checkForMissingObjects(StateList $list)
    {
        $db = $this->db;
        $bind = [$this->environment->getNameChecksum()];
        $stmt = $db->prepare('CALL populate_pending_hosts_for_environment(?)');
        $stmt->execute($bind);
        $stmt = $db->prepare('CALL drop_obsolete_host_states_for_environment(?)');
        $stmt->execute($bind);
    }

    protected function wantsTransaction()
    {
        if ($this->useTransactions && ! $this->hasTransaction) {
            $this->db->beginTransaction();
            $this->hasTransaction = true;
        }
    }

    protected function closeTransaction()
    {
        if ($this->hasTransaction) {
            // TODO: try, rollback
            $this->db->commit();
            $this->hasTransaction = false;
        }
    }

    /**
     * @return \Predis\Client
     */
    protected function redis()
    {
        if ($this->redis === null) {
            $this->redis = Redis::instance(true);
        }

        return $this->redis;
    }

    public function __destruct()
    {
        unset($this->redis);
        unset($this->api);
        unset($this->db);
        unset($this->ddo);
    }
}
