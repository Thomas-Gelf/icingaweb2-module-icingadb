<?php

namespace Icinga\Module\Icingadb\Db\StateSummary;

class HostStateSummary extends StateSummary
{
    protected $table = 'host_state';

    protected $hostGroupChecksum;

    public function setHostGroupChecksum($checksum)
    {
        $this->hostGroupChecksum = $checksum;
        return $this;
    }

    protected function prepareQuery()
    {
        $query = parent::prepareQuery();
        $db = $query->getAdapter();
        if (null !== $this->hostGroupChecksum) {
            $sub = $db->select()->from(
                array('hgm' => 'hostgroup_member'),
                array('e' => '(1)')
            )->where(
                's.global_checksum = hgm.global_host_checksum'
            )->where(
                'hgm.global_hostgroup_checksum = ?',
                $this->hostGroupChecksum
            );

            $query->where('EXISTS ?', $sub);
        }

        return $query;
    }
}
