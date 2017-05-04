<?php

namespace Icinga\Module\Icingadb\ConfigSync;

use Icinga\Application\Benchmark;
use Icinga\Application\Logger;
use Icinga\Module\Icingadb\IcingaDb;
use Icinga\Module\Icingadb\IcingaConfigObject\IcingaConfigObject;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;
use Icinga\Module\Icingadb\Redis\IcingaRedisProxy;

class ChangeSetToDbWriter
{
    /** @var ChangeSet */
    protected $change;

    /** @var IcingaDb */
    protected $ddo;

    protected $redisProxy;

    protected $db;

    /** @var IcingaEnvironment */
    protected $env;

    public function __construct(
        ChangeSet $change,
        IcingaEnvironment $environment,
        $type
    ) {
        $this->env = $environment;
        $this->redisProxy = new IcingaRedisProxy($environment);
        $this->change = $change;
        $this->ddo    = $environment->getConnection();
        $this->db     = $environment->getDb();
        $this->type   = $type;
    }

    public function persistModifications()
    {
        if ($this->change->hasCreate()) {
            $this->ddo->runFailSafe(function () {
                $this->createNewObjects();
            });
        }

        if ($this->change->hasModify()) {
            $this->ddo->runFailSafe(function () {
                $this->storeModifiedObjects();
            });
        }

        if ($this->change->hasDelete()) {
            $this->ddo->runFailSafe(function () {
                $this->removeObsoleteObjects();
            });
        }
    }

    protected function createNewObjects()
    {
        /** @var IcingaConfigObject $class - not really, cheating for the IDE */
        $class = IcingaConfigObject::getClassForType($this->type);
        $typeName = IcingaConfigObject::normalizedTypeName($this->type);
        $count = 0;

        foreach ($this->change->getCreated()->fetchFromRedis(
            $this->redisProxy,
            $typeName
        ) as $key => $json) {
            $plain = json_decode($json);
            if ($plain === null) {
                Logger::info('Could not find %s on sync', $key);
                continue;
            }
            $count++;

            $object = $class::fromIcingaObject(
                $plain,
                $this->env
            );

            $object->store();
        }

        Benchmark::measure(sprintf('%d new objects will be stored', $count));
    }

    protected function storeModifiedObjects()
    {
        /** @var IcingaConfigObject $class - not really, cheating for the IDE */
        $class = IcingaConfigObject::getClassForType($this->type);
        $typeName = IcingaConfigObject::normalizedTypeName($this->type);
        $count = 0;

        foreach ($this->change->getModified()->fetchFromRedis(
            $this->redisProxy,
            $typeName
        ) as $key => $json) {
            $plain = json_decode($json);
            if ($plain === null) {
                Logger::info('Could not find %s on sync', $key);
                continue;
            }
            $count++;

            $new = $class::fromIcingaObject(
                $plain,
                $this->env
            );

            $class::load($new->get('global_checksum'), $this->ddo)
                ->replaceWith($new)
                ->store();
        }

        Benchmark::measure(sprintf('%d modified objects will be stored', $count));
    }

    protected function removeObsoleteObjects()
    {
        /** @var IcingaConfigObject $class - not really, cheating for the IDE */
        $class = IcingaConfigObject::getClassForType($this->type);
        $dummy = $class::create([]);
        $envSum = $this->env->getNameChecksum();
        $keys = [];
        $count = 0;

        foreach ($this->change->getDeleted()->getKeys() as $key) {
            $keys[] = sha1($envSum . $key, true);
            $count++;
        }

        $this->db->delete(
            $dummy->getTableName(),
            $this->db->quoteInto(
                'global_checksum IN (?)',
                $keys
            )
        );

        Benchmark::measure(sprintf('%d objects will be deleted', $count));
    }
}
