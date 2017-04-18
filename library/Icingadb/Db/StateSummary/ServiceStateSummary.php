<?php

namespace Icinga\Module\Icingadb\Db\StateSummary;

use Icinga\Module\Icingadb\IcingaConfigObject\IcingaHostConfig;

class ServiceStateSummary extends StateSummary
{
    protected $table = 'service_state';

    /** @var string */
    protected $hostChecksum;

    public function setHostChecksum($checksum)
    {
        $this->hostChecksum = $checksum;
        return $this;
    }

    public static function forHostConfig(IcingaHostConfig $host)
    {
        $summary = static::fromDb($host->getConnection());
        $summary->setHostChecksum($host->get('global_checksum'));
        return $summary;
    }

    protected function prepareQuery()
    {
        $query = parent::prepareQuery();
        if ($this->hostChecksum !== null) {
            $query->join(
                ['c' => 'service_config'],
                'c.global_checksum = s.global_checksum'
            )->where('host_checksum = ?', $this->hostChecksum);
        }

        return $query;
    }
}
