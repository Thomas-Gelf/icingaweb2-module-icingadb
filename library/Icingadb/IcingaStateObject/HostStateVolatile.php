<?php

namespace Icinga\Module\Icingadb\IcingaStateObject;

class HostStateVolatile extends VolatileStateObject
{
    const PREFIX = 'icinga:hoststate.';

    protected $table = 'host_state_volatile';
}
