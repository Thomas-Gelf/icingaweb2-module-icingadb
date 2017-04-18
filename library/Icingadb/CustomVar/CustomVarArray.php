<?php

namespace Icinga\Module\Icingadb\CustomVar;

class CustomVarArray extends CustomVarValue
{
    private $values = [];

    public function __construct(array $value)
    {
        foreach ($value as $item) {
            if ($item instanceof CustomVarValue) {
                $this->values[] = $item;
            } else {
                $this->values[] = CustomVarValue::detectType($item);
            }
        }
    }

    public function calculateChecksum()
    {
        $string = '';
        foreach ($this->values as $value) {
            $string .= $value->getChecksum();
        }

        return sha1($string, true);
    }

    public function toPlainObject()
    {
        $array = [];
        foreach ($this->values as $value) {
            $array[] = $value->toPlainObject();
        }

        return $array;
    }
}
