<?php

namespace Icinga\Module\Icingadb\View;

use Predis\Client;

class ServicesView extends ListView
{
    protected $predis;

    protected $hostChecksum;

    public function getAvailableColumns()
    {
        return array(

        );
    }

    public function setHostChecksum($checksum)
    {
        $this->hostChecksum = $checksum;
        return $this;
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
            'service'           => 's.name',
            'environment'       => 'e.name',
            'checksum'          => 's.name_checksum',
            'global_checksum'   => 's.global_checksum',
            'state'             => 'ss.state',
            'problem'           => 'ss.problem',
            'acknowledged'      => 'ss.acknowledged',
            'in_downtime'       => 'ss.in_downtime',
            'last_state_change' => 'ss.last_state_change',
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
                array('s' => 'service_config'),
                'h.global_checksum = s.host_checksum',
                array()
            )->join(
                array('ss' => 'service_state'),
                's.global_checksum = ss.global_checksum',
                array()
            )->join(
                ['e' => 'icinga_environment'],
                'ss.env_checksum = e.name_checksum',
                []
            )
            ->order('severity DESC')
            ->order('last_state_change DESC')
            ->limit(25);
        // echo $query;

        if (null !== $this->hostChecksum) {
            $query->where('h.global_checksum = ?', $this->hostChecksum);
        }

        return $query;
    }
}
