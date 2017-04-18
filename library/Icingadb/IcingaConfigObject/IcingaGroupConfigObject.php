<?php

namespace Icinga\Module\Icingadb\IcingaConfigObject;

use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;

abstract class IcingaGroupConfigObject extends IcingaConfigObject
{
    protected $keyName = 'global_checksum';

    /** @inheritdoc */
    protected $defaultProperties = array(
        'global_checksum' => null,
        'name_checksum'   => null,
        'env_checksum'    => null,
        'name'            => null,
        'name_ci'         => null,
        'label'           => null
    );

    public static function fromIcingaObject($object, IcingaEnvironment $environment)
    {
        $properties = array(
            'name'                      => $object->name,
            'environment'               => $environment,
            'label'                     => $object->display_name,
        );

        $group = static::create($properties, $environment->getConnection());
        return $group;
    }

    public function setName($name)
    {
        if ($name !== $this->get('name')) {
            parent::reallySet('name', $name);
            $this->set('name_ci', $name);
            $this->set('name_checksum', sha1($name, true));
        }
    }
}
