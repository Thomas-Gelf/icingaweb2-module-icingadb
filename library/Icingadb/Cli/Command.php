<?php

namespace Icinga\Module\Icingadb\Cli;

use Icinga\Application\Config;
use Icinga\Module\Icingadb\IcingaDb;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;
use Icinga\Module\Icingadb\Redis;
use Icinga\Cli\Command as CliCommand;

class Command extends CliCommand
{
    /** @var IcingaDb */
    private $icingaDb;

    /** @var Redis */
    private $redis;

    /** @var IcingaEnvironment */
    protected $environment;

    /**
     * @return \Predis\Client
     */
    protected function redis()
    {
        if ($this->redis === null) {
            $this->redis = Redis::instance(true);
        }

        return $this->redis;
    }

    /**
     * @return IcingaEnvironment
     */
    protected function getEnvironment()
    {
        if ($this->environment === null) {
            $env = $this->params->getRequired('environment');
            $this->environment = IcingaEnvironment::load($env, $this->icingaDb());
        }

        return $this->environment;
    }

    /**
     * @return IcingaDb
     */
    protected function icingaDb()
    {
        if ($this->icingaDb === null) {
            $resourceName = Config::module('icingadb')->get('db', 'resource');
            if ($resourceName) {
                $this->icingaDb = IcingaDb::fromResourceName($resourceName);
            } else {
                $this->fail('(icingadb) DDO is not configured correctly');
            }
        }

        return $this->icingaDb;
    }

    protected function setProcessTitle($title)
    {
        if (PHP_OS !== 'Darwin') {
            cli_set_process_title($title);
        }
    }

    protected function clearConnections()
    {
        $this->icingaDb = null;
        $this->redis = null;
        return $this;
    }
}
