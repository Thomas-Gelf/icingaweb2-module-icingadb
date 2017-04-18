<?php

namespace Icinga\Module\Icingadb\ConfigSync;

use Countable;
use Icinga\Module\Icingadb\Redis\IcingaRedisProxy;

class ChangeSetObjects implements Countable
{
    protected $count = 0;

    protected $objects = [];

    public function isEmpty()
    {
        return $this->count === 0;
    }

    public function count()
    {
        return $this->count;
    }

    public function getKeys()
    {
        return array_keys($this->objects);
    }

    public function fetchFromRedis(IcingaRedisProxy $proxy, $type)
    {
        if ($this->count() > 2500) {
            return $proxy->fetchAll($type);
        } else {
            $keys = [];
            foreach ($this->objects as $key => & $props) {
                $keys[] = $props[0]; // TODO: const -> NAME?
            }

            return $proxy->fetchByKey($type, $keys);
        }
    }

    public function set($sum, $value)
    {
        if (! array_key_exists($sum, $this->objects)) {
            $this->count++;
        }

        $this->objects[$sum] = $value;

        return $this;
    }
}
