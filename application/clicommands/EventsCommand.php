<?php

namespace Icinga\Module\Icingadb\Clicommands;

use Icinga\Module\Icingadb\Cli\Command;
use Icinga\Module\Icingadb\IcingaEventHandler;
use Icinga\Module\Icingadb\IcingaEventToRedisStreamer;

class EventsCommand extends Command
{
    /**
     * Process an Icinga 2 event stream from Redis
     *
     * This currently replicates volatile state to a local Redis instance
     * and fills the IcingaDB database. Should be replaced by a core feature
     * once finalizeds
     */
    public function processAction()
    {
        $env = $this->getEnvironment();
        $this->setProcessTitle('IcingaDB Event Stream: ' . $env->get('name'));
        $handler = new IcingaEventHandler($env);
        $handler->processEvents();
    }

    /**
     * Publish an Icinga 2 API event stream through Redis
     *
     * This is now an Icinga 2 core feature and should not be used anymore
     *
     * @deprecated
     */
    public function streamAction()
    {
        $env = $this->getEnvironment();
        $this->setProcessTitle('IcingaDB Event Stream');
        $streamer = new IcingaEventToRedisStreamer($env->getCoreApi());
        $streamer->stream();
    }
}
