<?php

namespace Icinga\Module\Icingadb;

use Icinga\Application\Config;
use Predis\Client as PredisClient;

class Redis
{
    protected static $redis;

    public static function requireAutoloader()
    {
        require_once 'predis/autoload.php';
    }

    /**
     * @param bool $new
     * @return PredisClient
     */
    public static function instance($new = false)
    {
        if ($new || self::$redis === null) {
            $config = Config::module('icingadb', 'config', true);

            $options = array(
                'host' => $config->get('redis', 'host', 'localhost'),
                'port' => $config->get('redis', 'port', 6379),
                'timeout' => 0.1
            );

            if ($password = $config->get('redis', 'password', null)) {
                $options['password'] = $password;
            }

            static::requireAutoloader();
            self::$redis = new PredisClient($options);
        }

        return self::$redis;
    }
}
