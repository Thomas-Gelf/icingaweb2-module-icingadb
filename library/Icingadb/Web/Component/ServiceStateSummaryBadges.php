<?php

namespace Icinga\Module\Icingadb\Web\Component;

use Icinga\Module\Icingadb\IcingaStateObject\ServiceState;

class ServiceStateSummaryBadges extends StateSummaryBadges
{
    protected $baseUrl = 'icingadb/services';

    public function getStateNameFromSeverity($severity)
    {
        return ServiceState::serviceSeverityStateName($severity);
    }
}
