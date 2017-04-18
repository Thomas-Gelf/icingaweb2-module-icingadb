<?php

namespace Icinga\Module\Icingadb\CustomVar;

class CustomVarFloat extends CustomVarValue
{
    private $value;

    public function __construct($value)
    {
        $this->value = (float) $value;
    }

    public function calculateChecksum()
    {
        return sha1(sprintf('%.9F', $this->value), true);
    }

    public function toPlainObject()
    {
        return $this->value;
    }
}
