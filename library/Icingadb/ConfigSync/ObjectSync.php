<?php

namespace Icinga\Module\Icingadb\ConfigSync;

use Icinga\Module\Icingadb\IcingaDb;
use Icinga\Module\Icingadb\IcingaConfigObject\IcingaConfigObject;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;

abstract class ObjectSync
{
    const OBJECT_NAME = 0;

    const NAME_CHECKSUM = 1;

    // const GLOBAL_CHECKSUM = 2;

    const PROPERTIES_CHECKSUM = 2;

    const GROUPS_CHECKSUM = 3;

    const VARS_CHECKSUM = 4;

    /** @var IcingaEnvironment */
    private $env;

    /** @var string */
    protected $objectClassName;

    /** @var string */
    protected $configTableName;

    public function __construct(IcingaEnvironment $env)
    {
        $this->env = $env;
    }

    public function run()
    {
        $diff = new FullDiff($this->getType(), $this->env);
        $changes = $diff->run();
        $writer = new ChangeSetToDbWriter($changes, $this->env, $this->getType());
        $writer->persistModifications();
    }

    abstract public function getType();

    public function getPluralType()
    {
        return $this->getType() . 's';
    }

    public function getConfigTableName()
    {
        if ($this->configTableName === null) {
            $this->configTableName = $this->getType() . '_config';
        }

        return $this->configTableName;
    }

    protected function getObjectConfigTableName()
    {
        return $this->getType() . '_config';
    }

    protected function getObjectClassName()
    {
        if ($this->objectClassName === null) {
            $this->objectClassName =
                'Icinga\\Module\\Icingadb\\IcingaConfigObject\\Icinga'
                . ucfirst($this->getType())
                . 'Config';
        }

        return $this->objectClassName;
    }
}
