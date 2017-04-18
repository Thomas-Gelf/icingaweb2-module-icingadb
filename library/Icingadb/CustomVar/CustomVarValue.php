<?php

namespace Icinga\Module\Icingadb\CustomVar;

use stdClass;

abstract class CustomVarValue
{
    protected $checksum;

    public static function detectType($value)
    {
        if (is_object($value)) {
            if ($value instanceof CustomVarValue) {
                return $value;
            } elseif ($value instanceof stdClass) {
                return new CustomVarDictionary($value);
            } else {
                throw new CustomVarTypeException(
                    'Unable to create a CustomVar from an object of type "%s"',
                    get_class($value)
                );
            }
        } elseif (is_array($value)) {
            return new CustomVarArray($value);
        } elseif (is_string($value)) {
            return new CustomVarString($value);
        } elseif (is_bool($value)) {
            return new CustomVarBool($value);
        } elseif (is_int($value)) {
            return new CustomVarInt($value);
        } elseif (is_float($value)) {
            return new CustomVarFloat($value);
        } else {
            // TODO: What else might there be?
            throw new CustomVarTypeException(
                'Unable to detect CustomVar type: %s',
                var_export($value)
            );
        }
    }

    public function getChecksum()
    {
        if ($this->checksum === null) {
            $this->checksum = $this->calculateChecksum();
        }

        return $this->checksum;
    }

    public function toJson()
    {
        return json_encode($this->toPlainObject());
    }

    abstract public function calculateChecksum();

    abstract public function toPlainObject();
}
