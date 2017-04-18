<?php

namespace Icinga\Module\Icingadb\IcingaStateObject;

class ServiceState extends StateObject
{
    protected $table = 'service_state';

    protected function calculateSeverity()
    {
        $sev = parent::calculateSeverity();

        // TODO: add host state to the mix

        return $sev;
    }

    public static function getSortingStateFor($state)
    {
        return self::$serviceStateSortMap[$state];
    }
}
