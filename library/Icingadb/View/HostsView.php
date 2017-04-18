<?php

namespace Icinga\Module\Icingadb\View;

use Predis\Client;

class HostsView extends ListView
{
    protected $predis;

    public function getAvailableColumns()
    {
        return array(

        );
    }

    public function setRedis(Client $predis)
    {
        $this->predis = $predis;
        return $this;
    }

    public function getColumns()
    {
        return [
            'host'              => 'h.name',
            'environment'       => 'e.name',
            'checksum'          => 'h.name_checksum',
            'global_checksum'   => 'h.global_checksum',
            'state'             => 'hs.state',
            'problem'           => 'hs.problem',
            'acknowledged'      => 'hs.acknowledged',
            'in_downtime'       => 'hs.in_downtime',
            'last_state_change' => 'hs.last_state_change',
        ];
    }

    protected function prepareBaseQuery()
    {
        $query = $this->db()
            ->select()
            ->from(
                ['h' => 'host_config'],
                []
            )->join(
                array('hs' => 'host_state'),
                'h.global_checksum = hs.global_checksum',
                array()
            )->join(
                ['e' => 'icinga_environment'],
                'hs.env_checksum = e.name_checksum',
                []
            )
            ->order('severity DESC')
            ->order('last_state_change DESC')
            ->limit(25);
        // echo $query;

        return $query;
    }
}
