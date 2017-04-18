<?php

namespace Icinga\Module\Icingadb\ConfigSync;

use Icinga\Application\Benchmark;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;
use Predis\Connection\ConnectionException;

class ConfigSync
{
    /** @var IcingaEnvironment */
    protected $env;

    protected $allTypes = [
        'HostGroup'    => 'hostgroup',
        'Host'         => 'host',
        'UserGroup'    => 'usergroup',
        'User'         => 'user',
        'ServiceGroup' => 'servicegroup',
        'Service'      => 'service'
    ];

    public function __construct(IcingaEnvironment $env)
    {
        $this->env = $env;
    }

    public function runOnce()
    {
        $this->runFullSync();
    }

    public function getAllTypes()
    {
        return $this->allTypes;
    }

    protected function isValidType($type)
    {
        return array_key_exists($type, $this->allTypes);
    }

    public function runFullSync()
    {
        $this->runforTypes($this->getAllTypes());
    }

    protected function runForTypes(array $types)
    {
        foreach ($types as $type) {
            $this->runForType($type);
        }
    }

    protected function runForType($type)
    {
        $env = $this->env;
        $diff = new FullDiff($type, $env);
        $changes = $diff->run();
        $writer = new ChangeSetToDbWriter($changes, $env, $type);
        $writer->persistModifications();

        if ($type === 'host') {
            $sync = new HostGroupMembershipSync($env);
            $sync->run();
        }
    }

    public function runForever()
    {
        $this->runOnce();
        $this->notify();
        Benchmark::reset();
        $this->subscribeConfigChanges();
    }

    protected function notify()
    {
        // foreach ()
        echo Benchmark::dump();
    }

    public function subscribeActions()
    {
    }

    public function subscribeConfigChanges()
    {
        $env = $this->env;
        $redis = $env->getRedis();
        $subRedis = $env->createNewRedisConnection();
        try {
            $subRedis->pubSubLoop(
                ['subscribe' => [
                    'icinga:config:dump',
                    'icinga:config:update',
                    'icinga:config:delete',
                    'icinga:cib',
                ]],
                function ($l, $msg) use ($env) {
                    switch ($msg->kind) {
                        case 'message':
                            switch($msg->channel) {
                                case 'icinga:config:dump':
                                    $type = $msg->payload;
                                    if ($this->isValidType($type)) {
                                        echo "GO $type\n";
                                        $this->runForTypes([strtolower($type)]);
                                        $this->notify();
                                        Benchmark::reset();
                                    } else {
                                        echo "NOGO: $type\n";
                                    }
                                    return true;
                                    break;
                                case 'icinga:cib':
                                    $payload = json_decode($msg->payload);
                                    printf(
                                        "1min host checks on %s: %d\n",
                                        $env->get('name'),
                                        $payload->active_host_checks_1min
                                    );
                                    // print_r($payload);
                                    break;
                            }
                            break;
                        case 'subscribe':
                            printf(
                                "New subscription to %s\n",
                                $msg->channel
                            );
                            break;
                        default:
                            printf(
                                "Got %s: %s",
                                $msg->channel,
                                $msg->payload
                            );
                    }
                }
            );
        } catch (ConnectionException $e) {
            $stream = $e->getConnection()->getResource();
            $metadata = var_export(stream_get_meta_data($stream), true);
            var_dump($metadata);
            throw $e;
        }
//        ExecuteQuery({ "PUBLISH", "icinga:config:dump", typeName });
//        ExecuteQuery({ "PUBLISH", "icinga:config:delete", typeName + ":" + objectName });
//        ExecuteQuery({ "PUBLISH", "icinga:config:update", typeName + ":" + objectName + "!" + checkSumBody });
    }

    // TODO: Sync foreverr
    //  Logger::error($e->getMessage() . "\n" . $e->getTraceAsString());
}
