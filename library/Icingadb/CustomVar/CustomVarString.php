<?php

namespace Icinga\Module\Icingadb\CustomVar;

class CustomVarString extends CustomVarValue
{
    private $value;

    public function __construct($value)
    {
        $this->value = (string) $value;
    }

    public function calculateChecksum()
    {
        return sha1($this->value, true);
    }

    public function toPlainObject()
    {
        return $this->value;
    }
}
