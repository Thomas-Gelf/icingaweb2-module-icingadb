<?php

namespace Icinga\Module\Icingadb\IcingaConfigObject;

use Icinga\Exception\ProgrammingError;
use Icinga\Module\Icingadb\DdoObject;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;

// compat
abstract class IcingaConfigObject extends DdoObject
{
    /** @inheritdoc */
    protected $keyName = 'global_checksum';

    public static function getClassForType($type)
    {
        return __NAMESPACE__
            . '\\Icinga'
            . static::normalizedTypeName($type)
            . 'Config';
    }

    public static function normalizedTypeName($type)
    {
        if (substr($type, -5) === 'group') {
            return ucfirst(substr($type, 0, -5)) . 'Group';
        } else {
            return ucfirst($type);
        }
    }

    /**
     * Create a DDO host object from an Icinga 2 API host object
     *
     * @param   object            $apiObject    The Core API raw object
     * @param   IcingaEnvironment $environment  The related Icinga Environment
     *
     * @return  static
     */
    public static function fromApiObject($apiObject, IcingaEnvironment $environment)
    {
        $object = $apiObject->attrs;
        return static::fromIcingaObject($object, $environment);
    }

    /**
     * @param \stdClass $object
     * @param IcingaEnvironment $environment
     * @throws ProgrammingError
     * @return static
     */
    public static function fromIcingaObject($object, IcingaEnvironment $environment)
    {
        throw new ProgrammingError('%s did not implement ::propertiesFromIcingaObject()');
    }

    public function setEnvironment(IcingaEnvironment $environment)
    {
        $checksum = sha1($environment->get('name'), true);
        if ($checksum !== $this->get('env_checksum')) {
            $this->set('env_checksum', $checksum);
            $this->set(
                'global_checksum',
                sha1($checksum . $this->get('name_checksum'), true)
            );
        }

        return $this;
    }

    public function setName($name)
    {
        $pos = strpos($name, '!');
        if ($pos === false) {
            $myName = $name;
        } else {
            $myName = substr($name, $pos + 1);
        }
        if ($myName !== $this->get('name')) {
            parent::reallySet('name', $myName);
            $this->set('name_ci', $myName);
            $this->set('name_checksum', sha1($name, true));
        }
    }

    public function setPackage($name)
    {
        $this->set('package_checksum', sha1($name, true));
    }

    public function setZone($name)
    {
        $this->set('zone_checksum', sha1($name, true));
    }

    /**
     * {@inheritdoc}
     * Interpret properties ending w/ _enabled as boolean
     */
    public function propertyIsBoolean($property)
    {
        if (substr($property, -8) === '_enabled' || $property === 'volatile') {
            return true;
        }
        return parent::propertyIsBoolean($property);
    }
}
