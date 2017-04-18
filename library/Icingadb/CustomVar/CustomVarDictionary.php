<?php

namespace Icinga\Module\Icingadb\CustomVar;

class CustomVarDictionary extends CustomVarValue
{
    private $values = [];

    public function __construct($value)
    {
        foreach ($value as $key => $item) {
            if ($item instanceof CustomVarValue) {
                $this->values[$key] = $item;
            } else {
                $this->values[$key] = CustomVarValue::detectType($item);
            }
        }

        ksort($this->values);
    }

    public function calculateChecksum()
    {
        $string = '';
        foreach ($this->values as $key => $value) {
            $string .= sha1(
                "$key=" . $value->getChecksum(),
                true
            );
        }

        return sha1($string, true);
    }

    public function toPlainObject()
    {
        $dict = [];
        foreach ($this->values as $value) {
            $array[] = $value->toPlainObject();
        }

        return (object) $dict;
    }
}
