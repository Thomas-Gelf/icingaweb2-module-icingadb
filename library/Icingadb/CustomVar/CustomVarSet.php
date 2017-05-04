<?php

namespace Icinga\Module\Icingadb\CustomVar;

use Icinga\Module\Icingadb\IcingaDb;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;

class CustomVarSet
{
    protected $globalChecksum;

    protected $icingaChecksum;

    protected $envChecksum;

    protected $checksum;

    /** @var CustomVar[] */
    protected $vars = [];

    private final function __construct()
    {

    }

    public function storeToDb(IcingaDb $db)
    {
        $sum = $this->globalChecksum;

        $db->insert('custom_var_set', [
            'global_checksum'     => $sum,
            'env_checksum'        => $this->envChecksum,
            'icinga_set_checksum' => $this->icingaChecksum,
            'set_checksum'        => $this->checksum
        ]);

        foreach ($this->vars as $var) {
            $db->insert('custom_var_set_var', [
                'set_checksum' => $sum,
                'var_checksum' => $var->getChecksum()
            ]);
        }

        return $this;
    }

    /**
     * @param IcingaEnvironment $env
     * @param $icingaChecksum
     * @param array $vars
     * @return static
     */
    public static function createLegacy(IcingaEnvironment $env, $icingaChecksum, array $vars)
    {
        $self = new static();
        $self->envChecksum = $env->getNameChecksum();
        $self->icingaChecksum = $icingaChecksum;
        foreach ($vars as $var) {
            $self->addVar($var);
        }

        $self->recalculateChecksum();
        return $self;
    }

    public function recalculateChecksum()
    {
        $parts = [];
        foreach ($this->vars as $var) {
            $parts[] = $var->getChecksum();
        }

        $this->checksum = sha1(implode('|', $parts), true);
        $this->globalChecksum = sha1($this->envChecksum . $this->checksum, true);
    }

    public function addVar(CustomVar $var)
    {
        $this->vars[$var->getChecksum()] = $var;
    }
}
