<?php

namespace Icinga\Module\Icingadb\CustomVar;

class CustomVarInt extends CustomVarValue
{
    private $value;

    public function __construct($value)
    {
        $this->value = (int) $value;
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
