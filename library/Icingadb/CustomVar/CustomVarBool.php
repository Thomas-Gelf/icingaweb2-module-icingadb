<?php

namespace Icinga\Module\Icingadb\CustomVar;

class CustomVarBool extends CustomVarValue
{
    private $value;

    public function __construct($value)
    {
        $this->value = (bool) $value;
    }

    public function calculateChecksum()
    {
        return sha1((string) $this->value, true);
    }

    public function toPlainObject()
    {
        return $this->value;
    }
}
