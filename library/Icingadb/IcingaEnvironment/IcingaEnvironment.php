<?php

namespace Icinga\Module\Icingadb\IcingaEnvironment;

use Icinga\Module\Icingadb\DdoObject;
use Icinga\Module\Icingadb\Redis;
use Icinga\Module\Director\Db;
use Predis\Client as PredisClient;

class IcingaEnvironment extends DdoObject
{
    protected $defaultProperties = [
        'name'          => null,
        'name_checksum' => null,
        'director_db'   => null,
    ];

    protected $table = 'icinga_environment';

    protected $keyName = 'name';

    private $directorDb;

    private $redis;

    public function setName($name)
    {
        if ($name !== $this->get('name')) {
            parent::reallySet('name', $name);
            $this->set('name_checksum', sha1($name, true));
        }
    }

    public function generateGlobalChecksum($name)
    {
        return sha1(
            $this->getNameChecksum() . sha1($name, true),
            true
        );
    }

    /**
     * @return Db
     */
    public function getDirectorDb()
    {
        if ($this->directorDb === null) {
            $this->directorDb = Db::fromResourceName($this->get('director_db'));
        }

        return $this->directorDb;
    }

    public function getDeploymentEndpoint()
    {
        return $this->getDirectorDb()->getDeploymentEndpoint();
    }

    public function getCoreApi()
    {
        return $this->getDeploymentEndpoint()->api();
    }

    public function getNameChecksum()
    {
        return $this->get('name_checksum');
    }

    public function getRedis()
    {
        if ($this->redis === null) {
            $this->redis = $this->createNewRedisConnection();
        }

        return $this->redis;
    }

    public function createNewRedisConnection()
    {
        $ep = $this->getDeploymentEndpoint();
        $host = $ep->getResolvedProperty('host', $ep->getObjectName());
        $options = [
            'host' => $host,
            'port' => 5663,
            'timeout' => 5,
            'scheme' => 'tls',

            'password' => 'insecure',

            'ssl' => [
                'verify_peer' => false,
                'peer_name'   => $ep->getObjectName(),
            ]
        ];
        /*
        if ($password = $config->get('redis', 'password', null)) {
            $options['password'] = $password;
        }
        */

        Redis::requireAutoloader();
        return new PredisClient($options);
    }
}
