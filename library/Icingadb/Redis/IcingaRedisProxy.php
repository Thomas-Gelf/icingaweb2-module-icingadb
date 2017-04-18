<?php

namespace Icinga\Module\Icingadb\Redis;

use Icinga\Application\Benchmark;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;

class IcingaRedisProxy
{
    /** @var IcingaEnvironment */
    protected $env;

    /** @var LuaScriptRunner */
    protected $luaRunner;

    /** @var \Predis\Client */
    protected $predis;

    public function __construct(IcingaEnvironment $env)
    {
        $this->env = $env;
        $this->predis = $env->getRedis();
    }

    public function fetchAll($type)
    {
        return $this->predis->hgetall('icinga:config:' . ucfirst($type));
    }

    public function fetchByKey($type, $keys)
    {
        $key = 'icinga:config:' . ucfirst($type);

        return $this->predis->hmget($key, $keys);
    }

    public function fetchDistinctObjectVars($objectType)
    {
        $result = [];

        $plain = $this->luaRunner()->runScript(
            'fetchDistinctObjectVars',
            [$objectType]
        );

        foreach (json_decode($plain) as $checksum => $vars) {
            $result[hex2bin($checksum)] = $vars;
        }

        Benchmark::measure(sprintf(
            'Fetched %d distinct %s Vars from Redis',
            count($result),
            $objectType
        ));

        return $result;
    }

    /**
     * @param  string $objectType Host, Service
     * @return array
     */
    public function fetchObjectGroupSums($objectType)
    {
        $redis = $this->luaRunner();
        $cursor = 0;
        $groups = [];
        $cntRequests = 0;
        $cntMemberships = 0;
        $envSum = $this->env->getNameChecksum();

        Benchmark::measure(sprintf(
            'Ready to fetch %s group checksums',
            $objectType
        ));

        do {
            $cntRequests++;
            $result = $redis->runScript('fetchObjectGroupSums', [$objectType, $cursor]);
            foreach (json_decode($result[1]) as $key => $value) {
                $cntMemberships += count($value);
                $key = sha1($envSum . hex2bin($key), true);
                $sums = [];
                foreach ($value as $sum) {
                    $sums[] = sha1($envSum . hex2bin($sum), true);
                }
                $groups[$key] = $sums;
                sort($groups[$key], SORT_STRING);
            }

            $cursor = (int) $result[0];
        } while ($cursor !== 0);

        Benchmark::measure(sprintf(
            '%d requests, %d membership(s), %d object(s)',
            $cntRequests,
            $cntMemberships,
            count($groups)
        ));

        return $groups;
    }

    protected function luaRunner()
    {
        if ($this->luaRunner === null) {
            $this->luaRunner = new LuaScriptRunner($this->predis);
        }

        return $this->luaRunner;
    }
}
