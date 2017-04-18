<?php

namespace Icinga\Module\Icingadb\Web\Component;

use Icinga\Module\Icingadb\IcingaStateObject\HostState;

class HostStateSummaryBadges extends StateSummaryBadges
{
    protected $baseUrl = 'icingadb/hosts';

    public function getStateNameFromSeverity($severity)
    {
        return HostState::hostSeverityStateName($severity);
    }
}
