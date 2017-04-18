<?php

namespace Icinga\Module\Icingadb\ConfigSync;

use Icinga\Application\Benchmark;
use Icinga\Exception\ProgrammingError;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;
use Icinga\Module\Icingadb\Redis\IcingaRedisProxy;

abstract class GroupMembershipSync
{
    /** @var IcingaEnvironment */
    protected $env;

    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    /** @var string Short object type, like 'host' or 'service' */
    protected $type;

    protected $delete;

    protected $insert;

    public function __construct(IcingaEnvironment $env)
    {
        $this->env = $env;
        $this->db = $env->getDb();
    }

    public function run()
    {
        $this->insert = [];
        $this->delete = [];

        Benchmark::measure(sprintf(
            'Going to sync %s memberships',
            $this->type
        ));
        $wrong = $this->fetchOutdatedMemberships();
        $active = $this->fetchActiveMemberships();
        foreach ($wrong as $hostSum => $groupSums) {
            $this->prepareChanges(
                $hostSum,
                $groupSums,
                array_key_exists($hostSum, $active) ? $active[$hostSum] : null
            );
        }
        unset($wrong);
        unset($active);

        $this->insertMissingAssignments();
        $this->deleteObsoleteAssignments();
    }

    protected function insertMissingAssignments()
    {
        $envChecksum = $this->env->getNameChecksum();
        $table = $this->type . 'group_member';
        $db = $this->db;
        $db->beginTransaction();
        foreach ($this->insert as $row) {
            $db->insert($table, [
                'global_host_checksum'      => $row[0],
                'global_hostgroup_checksum' => $row[1],
                'env_checksum'              => $envChecksum,
            ]);
        }
        $db->commit();
    }

    protected function deleteObsoleteAssignments()
    {
        if (empty($this->delete)) {
            return;
        }

        $db = $this->db;
        $type = $this->type;
        $db->delete(
            "${type}group_member",
            $db->quoteInto("global_${type}_checksum IN (?)", $this->delete)
        );
    }

    protected function prepareChanges($hostSum, $storedGroups, $activeGroups)
    {
        if ($activeGroups === null) {
            $this->delete($hostSum, $storedGroups);
        } elseif ($storedGroups === null) {
            $this->insert($hostSum, $activeGroups);
        } else {
            $this->insert($hostSum, array_diff($activeGroups, $storedGroups));
            $this->delete($hostSum, array_diff($storedGroups, $activeGroups));
        }
    }

    protected function delete($hostSum, $groupSums)
    {
        foreach ($groupSums as $sum) {
            $this->delete[] = [$hostSum, $sum];
        }
    }

    protected function insert($hostSum, $groupSums)
    {
        foreach ($groupSums as $sum) {
            $this->insert[] = [$hostSum, $sum];
        }
    }

    public function fetchActiveMemberships()
    {
        $redis = new IcingaRedisProxy($this->env);
        return $redis->fetchObjectGroupSums(ucfirst($this->type));
    }

    public function fetchOutdatedMemberships()
    {
        $stored = [];
        foreach ($this->db->fetchAll(
            $this->prepareMismatchingPairsQuery()
        ) as $row) {
            $group = $row->global_hostgroup_checksum;
            $host = $row->global_host_checksum;

            if ($group === null) {
                $stored[$host] = [];
                continue;
            }

            if (array_key_exists($host, $stored)) {
                $stored[$host][] = $row->global_hostgroup_checksum;
            } else {
                $stored[$host] = [$row->global_hostgroup_checksum];
            }
        }

        Benchmark::measure(sprintf(
            'Got outdated memberships for %d objects',
            count($stored)
        ));

        return $stored;
    }

    public function getType()
    {
        if ($this->type === null) {
            $parts = preg_split('/\\\/', get_class($this));
            $class = end($parts);
            if (substr($class, -19) === 'GroupMembershipSync') {
                $this->type = lcfirst(substr($class, 0, -19));
            } else {
                throw new ProgrammingError(
                    'No GroupMemberShip type defined, unable to auto-detect'
                );
            }
        }

        return $this->type;
    }

    protected function prepareMismatchingPairsQuery()
    {
        $type = $this->getType();
        $inconsistentSums = $this->prepareInconsistentSumsQuery();

        return $this->db->select()->from(
            ['ics' => $inconsistentSums],
            [
                'global_host_checksum'       => 'ics.checksum',
                'global_hostgroup_checksum' => "gm.global_${type}group_checksum"
            ]
        )->joinLeft(
            ['gm' => "${type}group_member"],
            $this->db->quoteInto(
                "ics.checksum = gm.global_${type}_checksum AND gm.env_checksum = ?",
                $this->env->getNameChecksum()
            ),
            []
        )->order("ics.checksum")
            ->order("gm.global_${type}group_checksum");
    }

    protected function prepareInconsistentSumsQuery()
    {
        $type = $this->getType();

        $actualGroupsChecksum = "UNHEX(SHA1(COALESCE(GROUP_CONCAT("
            . "cgm.global_${type}group_checksum ORDER BY cgm.global_${type}group_checksum"
            . " ASC SEPARATOR ''),  '')))";

        $query = $this->db->select()->from(
            ['o' => "${type}_config"],
            [
                'checksum' => 'o.global_checksum',
                'expected_groups_checksum' => 'o.groups_checksum',
                'actual_groups_checksum'   => $actualGroupsChecksum
            ]
        )->joinLeft(
            ['cgm' => "${type}group_member"],
            'cgm.global_host_checksum = o.global_checksum',
            []
        )->where(
            $this->db->quoteInto('o.env_checksum = ?',
            $this->env->getNameChecksum())
        )->group('o.global_checksum')
            ->having('expected_groups_checksum != actual_groups_checksum');

        return $query;
    }
}
