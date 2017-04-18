<?php

namespace Icinga\Module\Icingadb\IcingaStateObject;

use Exception;
use Icinga\Application\Benchmark;
use Icinga\Module\Icingadb\DdoObject;
use ipl\Html\DeferredText;
use Predis\Client;

class VolatileStateObject extends DdoObject
{
    const PREFIX = 'icinga:somestate.';

    protected $table;

    protected $keyName = 'global_checksum';

    /** @var Client */
    protected static $predis;

    protected static $keysToFetch = [];

    protected $defaultProperties = array(
        'global_checksum'    => null,
        'command'          => null, // JSON, array
        'execution_start'  => null,
        'execution_end'    => null,
        'schedule_start'   => null,
        'schedule_end'     => null,
        'exit_status'      => null,
        'output'           => null,
        'performance_data' => null, // JSON, array
    );

    protected static $enrichKeys = [
        // 'global_checksum',
        'command',
        'execution_start',
        'execution_end',
        'schedule_start',
        'schedule_end',
        'exit_status',
        'output',
        'performance_data',
    ];

    protected static $deferredData;

    public static function setRedis(Client $redis)
    {
        static::$predis = $redis;
    }

    public function storeToRedis(Client $redis)
    {
        $key = static::prefix($this->get($this->keyName));
        $redis->set($key, $this->toJson());
        // TODO: expire in check_interval * attempts + timeout + some more seconds
        $redis->expire($key, 600);
        return $this;
    }

    public static function enrichRow($row)
    {
        if ((int) $row->state === 99) {
            return;
        }

        $keys = self::$enrichKeys;

        $checksum = $row->global_checksum;
        static::$keysToFetch[] = $checksum;
        foreach ($keys as $key) {
            $row->$key = DeferredText::create(function () use ($checksum, $key) {
                return HostStateVolatile::deferredKey($checksum, $key);
            });
        }
    }

    public static function deferredKey($checksum, $key)
    {
        if ($redis = static::$predis) {
            if (static::$deferredData === null) {
                static::fetchDeferredData();
            }

            if (static::$deferredData === false) {
                return null;
            }

            if (array_key_exists($checksum, static::$deferredData)) {
                return static::$deferredData[$checksum]->get($key);
            }
        }

        return null;
    }

    protected static function fetchDeferredData()
    {
        try {
            static::$deferredData = static::fromRedis(static::$predis, static::$keysToFetch);
        } catch (Exception $e) {
            static::$deferredData = false;
        }
    }

    public static function fromRedis(Client $redis, $checksum)
    {
        if (is_array($checksum)) {
            Benchmark::measure(sprintf('Fetching %d keys from redis', count($checksum)));
            $keys = array_map(__CLASS__ . '::prefix', $checksum);
            $encoded = $redis->mget($keys);
            $result = array();
            foreach ($encoded as $key => $json) {
                $result[$checksum[$key]] = static::create((array) json_decode($json));
            }

            Benchmark::measure('Result ready');
            return $result;
        } else {
            Benchmark::measure(sprintf('Fetching %s from redis', bin2hex($checksum)));
            $res = json_decode(
                $redis->get(HostStateVolatile::prefix($checksum))
            );
            if ($res) {
                return static::create((array) $res);
            } else {
                return static::create(array());
            }
        }
    }

    public static function failSafeFromRedis(Client $redis, $checksum)
    {
        try {
            return static::fromRedis($redis, $checksum);
        } catch (Exception $e) {
            return static::create([]);
        }
    }

    public static function removeFromRedis(Client $redis, $checksum)
    {
        $redis->del(HostStateVolatile::prefix($checksum));
    }

    protected static function prefix($key)
    {
        return static::PREFIX . $key;
    }

    public function toJson()
    {
        $props = (object) $this->getProperties();
        unset($props->{$this->keyName});
        return json_encode($props);
    }
}
