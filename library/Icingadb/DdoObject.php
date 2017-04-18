<?php

namespace Icinga\Module\Icingadb;

use Icinga\Data\Db\DbConnection;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;
use Icinga\Module\Director\Data\Db\DbObject;
use Icinga\Exception\ProgrammingError;
use Icinga\Util\StringHelper;

abstract class DdoObject extends DbObject
{
    protected $booleans = array();

    protected $timestamps = array();

    protected $shortTableName;

    public static function fromApiObject($apiObject, IcingaEnvironment $environment)
    {
        throw new ProgrammingError('%s did not implement ::fromApiObject()');
    }

    public static function fromRedisObject($object, IcingaEnvironment $environment)
    {
        throw new ProgrammingError('%s did not implement ::fromRedisObject()');
    }

    public function getUniqueName()
    {
        return $this->get('name');
    }

    public function set($key, $value)
    {
        // $this->checksum = null;
        if ($this->propertyIsBoolean($key)) {
            return parent::set($key, $this->normalizeBoolean($value));
        }

        if ($this->propertyIsTimestamp($key)) {
            return parent::set($key, $this->normalizeTimestamp($value));
        }

        $setter = 'set' . StringHelper::cname($key);
        if (method_exists($this, $setter)) {
            return $this->$setter($value);
        }

        return parent::set($key, $value);
    }

    public function getShortTableName()
    {
        if ($this->shortTableName === null) {
            // Not very usefuly anymore
            $parts = preg_split('/\\\/', get_class($this));
            $this->shortTableName = strtolower(substr(end($parts), 0, -6));
        }

        return $this->shortTableName;
    }

    public function propertyIsBoolean($property)
    {
        return in_array($property, $this->booleans);
    }

    public function propertyIsTimestamp($property)
    {
        return in_array($property, $this->timestamps);
    }

    protected function normalizeTimestamp($value)
    {
        return (int) round($value * 1000000);
    }

    protected function normalizeBoolean($value)
    {
        if ($value === 'y' || $value === '1' || $value === true || $value === 1) {
            return 'y';
        } elseif ($value === 'n' || $value === '0' || $value === false || $value === 0) {
            return 'n';
        } elseif ($value === '' || $value === null) {
            return null;
        } else {
            throw new ProgrammingError(
                'Got invalid boolean: %s',
                var_export($value, 1)
            );
        }
    }

    public function replaceWith(DdoObject $other)
    {
        $this->setProperties($other->getProperties());
        return $this;
    }
}
