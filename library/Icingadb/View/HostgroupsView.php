<?php

namespace Icinga\Module\Icingadb\View;

use Icinga\Module\Icingadb\IcingaStateObject\HostState;

class HostgroupsView extends ListView
{
    protected $predis;

    public function getAvailableColumns()
    {
        return [];
    }

    public function getColumns()
    {
        $columns = [
            'hostgroup' => 's.name',
            'env_name' => 's.env_name',
            'cnt_8'     => 'SUM(CASE WHEN s.severity = 8 THEN s.cnt ELSE 0 END)',
        ];
        $states = [
            HostState::ICINGA_DOWN,
            HostState::ICINGA_PENDING
        ];

        foreach ($states as $state) {
            $base = HostState::getSortingStateFor($state) << HostState::SHIFT_FLAGS;
            $num = $base | HostState::FLAG_NONE;
            $columns['cnt_' . $num] = sprintf('SUM(CASE WHEN s.severity = %d THEN s.cnt ELSE 0 END)', $num);
            $num = $base | HostState::FLAG_ACK;
            $columns['cnt_' . $num] = sprintf('SUM(CASE WHEN s.severity = %d THEN s.cnt ELSE 0 END)', $num);
            $num = $base | HostState::FLAG_DOWNTIME;
            $columns['cnt_' . $num] = sprintf('SUM(CASE WHEN s.severity = %d THEN s.cnt ELSE 0 END)', $num);
            $num = $base | HostState::FLAG_ACK | HostState::FLAG_DOWNTIME;
            $columns['cnt_' . $num] = sprintf('SUM(CASE WHEN s.severity = %d THEN s.cnt ELSE 0 END)', $num);
        }

        return $columns;
    }

    protected function getSubColumns()
    {
        return [
            'hg_global_checksum' => 'hg.global_checksum',
            'name'     => 'hg.name',
            'name_ci'  => 'hg.name_ci',
            'env_name' => 'e.name',
            'severity' => 'hs.severity',
            'cnt'      => 'COUNT(*)',
        ];
    }

    protected function prepareBaseQuery()
    {
        return $this->db()
            ->select()
            ->from(
                ['s' => $this->prepareSubQuery()->columns($this->getSubColumns())],
                []
            )->group('s.hg_global_checksum')
            ->order('s.name_ci')
            ->limit(250);
    }

    protected function prepareSubQuery()
    {
        return $this->db()
            ->select()
            ->from(['hg' => 'hostgroup'], [])
            ->join(['e' => 'icinga_environment'], 'hg.env_checksum = e.name_checksum', [])
            ->joinLeft(
                ['hgm' => 'hostgroup_member'],
                'hgm.global_hostgroup_checksum = hg.global_checksum',
                []
            )->joinLeft(
                ['hs' => 'host_state'],
                'hgm.global_host_checksum = hs.global_checksum',
                []
            )
            ->group('hg.global_checksum')
            ->group('hs.severity')
            ;
    }
}
