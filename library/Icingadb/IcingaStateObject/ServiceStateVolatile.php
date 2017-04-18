<?php

namespace Icinga\Module\Icingadb\IcingaStateObject;

class ServiceStateVolatile extends VolatileStateObject
{
    const PREFIX = 'icinga:servicestate.';

    protected $table = 'service_state_volatile';
}
