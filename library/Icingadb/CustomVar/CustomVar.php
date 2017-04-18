<?php

namespace Icinga\Module\Icingadb\CustomVar;

class CustomVar
{
    /** @var string */
    protected $name;

    /** @var CustomVarValue */
    protected $value;

    public function __construct($name, $value)
    {
        $this->name = $name;
        if ($value instanceof CustomVarValue) {
            $this->value = $value;
        } else {
            $this->value = CustomVarValue::detectType($value);
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getChecksum()
    {
        return sha1($this->name . '=' . $this->value->getChecksum(), true);
    }

    public function getValue()
    {
        return $this->value;
    }
}
