<?php

namespace Icinga\Module\Icingadb\ConfigSync;

use Icinga\Application\Benchmark;
use Icinga\Exception\IcingaException;
use Icinga\Module\Icingadb\IcingaConfigObject\IcingaConfigObject;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;
use SplFixedArray;

class FullDiff
{
    const OBJECT_NAME = 0;

    const NAME_CHECKSUM = 1;

    const PROPERTIES_CHECKSUM = 2;

    const GROUPS_CHECKSUM = 3;

    const VARS_CHECKSUM = 4;

    /** @var SplFixedArray[] */
    private $active;

    /** @var SplFixedArray[] */
    private $stored;

    /** @var IcingaEnvironment */
    private $env;

    /** @var ChangeSet */
    protected $changeSet;

    /** @var string */
    protected $objectType;

    /** @var IcingaConfigObject */
    protected $dummyObject;

    public function __construct($objectType, IcingaEnvironment $environment)
    {
        $this->objectType = $objectType;
        $this->env = $environment;
    }

    /**
     * @return ChangeSet
     */
    public function run()
    {
        $this->changeSet = new ChangeSet();
        Benchmark::measure(sprintf('FullDiff ready to diff %s', $this->objectType));
        $this->fetchStoredKeys();
        $cnt = count($this->stored);
        $this->changeSet->setCountOld($cnt);
        Benchmark::measure(sprintf('Got %d existing keys from IcingaDB', $cnt));
        $this->fetchActiveKeys();
        $cnt = count($this->active);
        $this->changeSet->setCountNew($cnt);
        Benchmark::measure(sprintf('Got %d active keys from Redis', $cnt));
        $this->detectObjectModifications();
        Benchmark::measure('Checked for modifications');
        // TODO -> CALL() $this->createMissingStates();
        Benchmark::measure('Created missing state entries');
        return $this->changeSet;
    }

    protected function dummyObject()
    {
        if ($this->dummyObject === null) {
            $class = IcingaConfigObject::getClassForType($this->objectType);
            $this->dummyObject = $class::create([]);
        }

        return $this->dummyObject;
    }

    protected function rowToFixedArray($row)
    {
        // TODO: Figure out whether groups will get props or vars. If not,
        //       this can be simplified
        if ($this->isGroup()) {
            return SplFixedArray::fromArray([
                $row->name,
                $row->name_checksum,
            ]);
        } else {
            return SplFixedArray::fromArray([
                $row->name,
                $row->name_checksum,
                $row->properties_checksum,
                $row->groups_checksum,
                $row->vars_checksum
            ]);
        }
    }

    protected function getKeyColumns()
    {
        if ($this->isGroup()) {
            return [
                'name'                => 'o.name',
                'name_checksum'       => 'o.name_checksum',
            ];
        } else {
            return [
                'name'                => 'o.name',
                'name_checksum'       => 'o.name_checksum',
                'properties_checksum' => 'o.properties_checksum',
                'groups_checksum'     => 'o.groups_checksum',
                'vars_checksum'       => 'o.vars_checksum'
            ];
        }
    }

    protected function isGroup()
    {
        return substr($this->objectType, -5) === 'group';
    }

    protected function fetchStoredKeys()
    {
        $envChecksum = $this->env->get('name_checksum');
        $db = $this->env->getConnection()->getDbAdapter();
        $query = $db->select()->from(
            ['o' => $this->getObjectConfigTableName()],
            $this->getKeyColumns()
        )->where(
            'env_checksum = ?',
            $envChecksum
        )->order('o.name_checksum');

        $this->stored = [];
        foreach ($db->fetchAll($query) as $row) {
            $this->stored[$row->name_checksum] = $this->rowToFixedArray($row);
        }
    }

    protected function fetchActiveKeys()
    {
        $key = 'icinga:config:' . IcingaConfigObject::normalizedTypeName($this->objectType) . ':checksum';
        $active = [];
        foreach ($this->fetchFullHashFromRedis($key) as $name => $json) {
            $row = json_decode($json);
            $key = hex2bin($row->name_checksum);
            if (property_exists($row,   'groups_checksum')) {
                $active[$key] = SplFixedArray::fromArray([
                    $name, // todo -> use less space, do not store this
                    $key,
                    hex2bin($row->groups_checksum),
                ]);
            } else {
                $active[$key] = SplFixedArray::fromArray([
                    $name, // todo -> use less space, do not store this
                    $key,
                ]);
            }
        }
        $this->active = $active;
    }

    protected function detectObjectModifications()
    {
        $changes = $this->changeSet;
        foreach ($this->active as $sum => & $sums) {
            if (array_key_exists($sum, $this->stored)) {
                $stored = & $this->stored[$sum];
                if ($stored[self::OBJECT_NAME] !== $sums[self::OBJECT_NAME]) {
                    $this->throwHashCollision(
                        $stored[self::OBJECT_NAME],
                        $sums[self::OBJECT_NAME],
                        $sum
                    );
                }
                /*
                if ($stored[self::PROPERTIES_CHECKSUM] !== $sums[self::PROPERTIES_CHECKSUM]) {
                    $changes->modify($sum, $sums);
                }
                */
            } else {
                $changes->create($sum, $sums);
            }
        }

        foreach (array_diff_key($this->stored, $this->active) as $sum => & $sums) {
            $changes->delete($sum, $sums);
        }
    }

    protected function fetchFullHashFromRedis($key)
    {
        $redis = $this->env->getRedis();

        return $redis->hgetall($key);
    }

    protected function throwHashCollision($name1, $name2, $checksum)
    {
        throw new IcingaException(
            'Congratulation, you discovered a SHA1 collision: %s and %s both give %s',
            $name1,
            $name2,
            bin2hex($checksum)
        );
    }

    protected function getObjectConfigTableName()
    {
        return $this->dummyObject()->getTableName();
        // return $this->objectType . '_config';
    }
}
