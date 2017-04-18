<?php

namespace Icinga\Module\Icingadb\Clicommands;

use Icinga\Module\Icingadb\Cli\Command;
use Icinga\Module\Icingadb\ConfigSync\ConfigSync;

/**
 * Icinga Object Config related tasks
 */
class ConfigCommand extends Command
{
    /**
     * Sync current config from Icinga API to IcingaDB
     *
     * This command runs forver and regularly syncs object configuration to DB.
     * Default interval is 60 seconds, please use --sleep <seconds> to adjust
     * this to fit your needs.
     */
    public function syncAction()
    {
        $env = $this->getEnvironment();
        $this->setProcessTitle('IcingaDB Config Sync: ' . $env->get('name'));

        // $sync = new ObjectSync($this->api(), $this->ddo(), $this->redis());
        // $sync->syncForever($sleepSeconds);
        $sync = new ConfigSync($env);
        if ($this->params->get('once')) {
            $sync->runOnce();
        } else {
            // $sleepSeconds = (int) $this->params->get('sleep', 60);
            $sync->runForever();
        }
    }
}
