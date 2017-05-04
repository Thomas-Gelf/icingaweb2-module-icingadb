<?php

namespace Icinga\Module\Icingadb\IcingaStateObject;

use Icinga\Exception\ProgrammingError;

class ServiceState extends StateObject
{
    protected $table = 'service_state';

    protected function calculateSeverity()
    {
        $sev = parent::calculateSeverity();

        // TODO: add host state to the mix

        return $sev;
    }

    public function getStateName()
    {
        $state = $this->get('state');
        if ($state === null) {
            throw new ProgrammingError('Got no state yet');
        }

        return self::$serviceStateNames[$state];
    }

    public function setState($state)
    {
        $state = (int) $state;
        // TODO: Remove 99
        if (($state >= 0 && $state <= 3) || $state === 99) {
            $this->reallySet('state', $state);
        } else {
            $this->reallySet('state', 3);
        }
    }

    public static function getSortingStateFor($state)
    {
        return self::$serviceStateSortMap[$state];
    }
}
