<?php

namespace Icinga\Module\Icingadb\Clicommands;

use Icinga\Application\Benchmark;
use Icinga\Module\Icingadb\Cli\Command;
use Icinga\Module\Icingadb\CustomVar\CustomVar;
use Icinga\Module\Icingadb\CustomVar\CustomVarBulkStore;
use Icinga\Module\Icingadb\CustomVar\CustomVarSet;
use Icinga\Module\Icingadb\CustomVar\CustomVarString;
use Icinga\Module\Icingadb\Director\DirectorDummyObjects;
use Icinga\Module\Icingadb\Redis\IcingaRedisProxy;

// legacy, just for tests - should not be used
class TroubleshootCommand extends Command
{
    /** @var IcingaRedisProxy */
    protected $redisProxy;

    public function gogogoAction()
    {
        $res = $this->getEnvironment()->getRedis()->hgetall('icinga:config:User');
        var_dump($res);
        Benchmark::measure(sprintf('Got %d hosts', count($res)));
    }

    public function initializeAction()
    {
        $env = new DirectorDummyObjects($this->getEnvironment()->getDirectorDb());
        $env->createBaseObjects();
    }

    public function hostvarsAction()
    {
        $icingaDb = $this->icingaDb();
        $env = $this->getEnvironment();
        $distinct = $this->redisProxy()->fetchDistinctObjectVars('Host');
        $store = new CustomVarBulkStore($icingaDb);

        $db = $icingaDb->getDbAdapter();
        $query = $db->select()
            ->from('custom_var_set', 'icinga_set_checksum')
            ->where('env_checksum = ?', $env->getNameChecksum());
        $existing = $db->fetchCol($query);
        $existing = array_flip($existing);

        /** @var CustomVarSet[] $newSets */
        $newSets = [];

        foreach ($distinct as $setSum => $vars) {
            if (array_key_exists($setSum, $existing)) {
                $setVars = null;
            } else {
                $setVars = [];
            }

            foreach ((array) $vars as $name => $value) {
                $var = new CustomVar($name, $value);
                $store->addCustomVar($var);

                if (null !== $setVars) {
                    $setVars[] = $var;
                }
            }

            if (null !== $setVars) {
                $newSets[] = CustomVarSet::createLegacy($env, $setSum, $setVars);
            }
        }

        $db->beginTransaction();
        foreach ($newSets as $set) {
            $set->storeToDb($icingaDb);
        }
        $store->store();
        $db->commit();
    }

    public function hostgroupsAction()
    {
        print_r($this->redisProxy()->fetchObjectGroupSums('Host'));
    }

    public function servicevarsAction()
    {
        print_r($this->redisProxy()->fetchDistinctObjectVars('Service'));
    }

    public function servicegroupsAction()
    {
        print_r($this->redisProxy()->fetchObjectGroupSums('Service'));
    }

    /*

    host_config -> vars_checksum -> vars_set -> set_checksum, var_checksum
    //
     */

    /*
    public function initrawgroupsAction()
    {
        // Full import, will fail on duplicates
        $env = $this->getEnvironment();
        $envSum = $env->getNameChecksum();
        $db = $env->getDb();
        $redis = $env->getRedis();
        $groups = $redis->hgetall('icinga:config:HostGroup');
        $db->beginTransaction();
        foreach ($groups as $group) {
            $group = json_decode($group);
            $name = $group->__name;
            $nameSum = sha1($name, true);
            $db->insert(
                'hostgroup',
                [
                    'global_checksum' => sha1($envSum . $nameSum, true),
                    'name_checksum' => $nameSum,
                    'env_checksum' => $envSum,
                    'name' => $name,
                    'name_ci' => $name,
                    'label' => $group->display_name,
                ]
            );
        }
        $db->commit();
    }
*/
/*
    public function hostgroupmemberAction()
    {
        $sync = new HostGroupMembershipSync($this->getEnvironment());
        $sync->run();
    }
*/
    protected function redisProxy()
    {
        if ($this->redisProxy === null) {
            $this->redisProxy = new IcingaRedisProxy($this->getEnvironment());
        }

        return $this->redisProxy;
    }
}
