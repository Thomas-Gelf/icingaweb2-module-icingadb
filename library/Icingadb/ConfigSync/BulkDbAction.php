<?php

namespace Icinga\Module\Icingadb\ConfigSync;

use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;

abstract class BulkDbAction
{
    /** @var IcingaEnvironment */
    protected $env;

    /** @var ChangeSet */
    protected $changes;

    public function __construct(IcingaEnvironment $env, ChangeSet $changes)
    {
        $this->env = $env;
        $this->changes = $changes;
    }
}
