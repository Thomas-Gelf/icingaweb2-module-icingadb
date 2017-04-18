<?php

namespace Icinga\Module\Icingadb\Director;

use Icinga\Module\Director\Db;
use Icinga\Module\Director\Objects\IcingaCommand;
use Icinga\Module\Director\Objects\IcingaCommandArgument;
use Icinga\Module\Director\Objects\IcingaHost;

class DirectorDummyObjects
{
    /** @var Db */
    protected $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    public function createHosts($count)
    {
        $ddb = $this->db;
        $db = $ddb->getDbAdapter();
        $db->beginTransaction();
        // $vars = (object) [];
        for ($i = 1; $i <= $count; $i++) {
            $name = 'test' . $i . '.example.com';
            $host = IcingaHost::create([
                'object_type' => 'object',
                'object_name' => $name,
                'address'     => '127.0.0.' . (($i % 253) + 1),
                'imports'     => 'DDO Random Host',
                'zone'        => 'satellites1',
                // 'vars'        => $vars
            ], $ddb);
            $host->store();
        }

        $db->commit();
    }

    public function createBaseObjects()
    {
        $this->createRandomCheckCommand();
        $this->createRandomHostTemplate();
        $this->createHosts(100000);
    }

    public function createRandomCheckCommand()
    {
        $db = $this->db;
        $cmdName = 'DDO random fortune';

        $argument = IcingaCommandArgument::create([
            'argument_name'   => '(no key)',
            'argument_value'  => 'var rand = Math.random() * 1000
    if (host.last_check == -1 || host.state == 0) {
        if (rand > 995) {
            return 2
        } else {
            return 0
        }
    } else {
        if (rand > 900) {
            return 0
        } else {
            return 2
        }
    }
',
            'argument_format' => 'expression',
            'key_string'      => null,
            'description'     => null,
            'skip_key'        => 'y',
            // 'set_if_format'   => 'string',
        ], $db);

        if (IcingaCommand::exists($cmdName, $db)) {
            $command = IcingaCommand::load($cmdName, $db);
            $command->command = '/usr/bin/dummy-fortune';
            $argument->command_id = $command->get('id');
        } else {
            $command = IcingaCommand::create([
                'object_name'     => $cmdName,
                'object_type'     => 'object',
                'methods_execute' => 'PluginCheck',
                'command'         => '/usr/bin/dummy-fortune',
            ], $db);
        }

        $command->arguments()->set('(no key)', $argument);

        if ($command->hasBeenModified()) {
            $command->store();
        }
    }

    public function createRandomHostTemplate()
    {
        $db = $this->db;
        $hostName = 'DDO Random Host';
        if (IcingaHost::exists($hostName, $db)) {
            $host = IcingaHost::load($hostName, $db);
        } else {
            $host = IcingaHost::create([
                'object_type' => 'template',
                'object_name' => $hostName
            ], $db);
        }

        $properties = [
            'max_check_attempts'    => 3,
            'check_interval'        => 60,
            'retry_interval'        => 30,
            'enable_notifications'  => 'y',
            'enable_active_checks'  => 'y',
            'enable_passive_checks' => 'y',
            'enable_event_handler'  => 'y',
            'enable_perfdata'       => 'y',
            'volatile'              => 'n',
            'check_command'         => 'DDO random fortune',
        ];

        foreach ($properties as $key => $val) {
            $host->set($key, $val);
        }

        if ($host->hasBeenModified()) {
            $host->store();
        }
    }
}
