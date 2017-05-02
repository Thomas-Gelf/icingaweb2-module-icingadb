<?php

namespace Icinga\Module\Icingadb\Web;

use Icinga\Application\Benchmark;
use Icinga\Application\Config;
use Icinga\Exception\ConfigurationError;
use ipl\Html\Link;
use ipl\Web\CompatController;
use Icinga\Module\Icingadb\IcingaDb;
use Icinga\Module\Icingadb\Redis;
use Icinga\Module\Director\Db;
use Icinga\Module\Director\Objects\IcingaEndpoint;

class Controller extends CompatController
{
    /** @var \Zend_Db_Adapter_Abstract */
    private $db;

    /** @var IcingaDb */
    private $icingaDb;

    /** @var Redis */
    private $redis;

    private $directorDb;

    private $api;

    protected function createAddLink($what)
    {
        return Link::create(
            'Add',
            sprintf('director/%s/add', $what),
            [],
            [
                'class' => 'icon-plus',
                'data-base-target' => '_next'
            ]
        );
    }

    /**
     * @return \Zend_Db_Adapter_Abstract
     */
    protected function db()
    {
        if ($this->db === null) {
            $this->db = $this->icingaDb()->getDbAdapter();
        }

        return $this->db;
    }

    /**
     * @return \Predis\Client;
     */
    protected function redis()
    {
        if ($this->redis === null) {
            try {
                $this->redis = Redis::instance(true);
            } catch (\Exception $e) {

            }
        }

        return $this->redis;
    }

    /**
     * @param null $endpointName
     * @return \Icinga\Module\Director\Core\CoreApi
     */
    protected function api($endpointName = null)
    {
        if ($this->api === null) {
            if ($endpointName === null) {
                $endpoint = $this->directorDb()->getDeploymentEndpoint();
            } else {
                $endpoint = IcingaEndpoint::load($endpointName, $this->icingaDb());
            }

            $this->api = $endpoint->api();
        }

        return $this->api;
    }

    /**
     * @return Db
     */
    protected function directorDb()
    {
        if ($this->directorDb === null) {
            // Hint: not using $this->Config() intentionally. This allows
            // CLI commands in other modules to use this as a base class.
            $resourceName = Config::module('director')->get('db', 'resource');
            if ($resourceName) {
                $this->directorDb = Db::fromResourceName($resourceName);
            }
        }

        return $this->directorDb;
    }

    /**
     * @return IcingaDb
     * @throws ConfigurationError
     */
    protected function icingaDb()
    {
        if ($this->icingaDb === null) {
            Benchmark::measure('Getting icingaDb');

            $resourceName = $this->Config()->get('db', 'resource');
            if ($resourceName) {
                $this->icingaDb = IcingaDb::fromResourceName($resourceName);
                Benchmark::measure('Created (and connected) IcingaDB resource');
            } else {
                throw new ConfigurationError('(icingadb) DB is not configured correctly');
            }
        }

        return $this->icingaDb;
    }
}
