<?php

namespace Icinga\Module\Icingadb\IcingaStateObject;

use Icinga\Exception\ProgrammingError;

class HostState extends StateObject
{
    protected $table = 'host_state';

    public function getStateName()
    {
        $state = $this->get('state');
        if ($state === null) {
            throw new ProgrammingError('Got no state yet');
        }

        return self::$hostStateNames[$state];
    }

    public function setState($state)
    {
        $state = (int) $state;
        if ($state === 0 || $state === 1) {
            $this->reallySet('state', 0);
        } else {
            $this->reallySet('state', 2);
        }
    }

    /**
     * @return HostStateVolatile
     */
    public function getVolatile()
    {
        $props = $this->volatile;
        $props['global_checksum'] = $this->get('global_checksum');
        return HostStateVolatile::create($props);
    }

    public static function getSortingStateFor($state)
    {
        return self::$hostStateSortMap[$state];
    }
}
