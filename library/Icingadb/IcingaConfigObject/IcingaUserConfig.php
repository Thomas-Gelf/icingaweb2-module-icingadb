<?php

namespace Icinga\Module\Icingadb\IcingaConfigObject;

use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;

class IcingaUserConfig extends IcingaConfigObject
{
    use IcingaConfigObjectGroups;

    /** @inheritdoc */
    protected $table = 'user_config';

    /** @inheritdoc */
    protected $shortTableName = 'user';

    /** @inheritdoc */
    protected $defaultProperties = array(
        'global_checksum'      => null,
        'name_checksum'        => null,
        'env_checksum'         => null,
        'package_checksum'     => null,
        'zone_checksum'        => null,
        'properties_checksum'  => null,
        'groups_checksum'      => null,
        'vars_checksum'        => null,
        'period_checksum'      => null,
        'name'                 => null,
        'name_ci'              => null,
        'label'                => null,
        'enable_notifications' => null,
    );

    public static function fromIcingaObject($object, IcingaEnvironment $environment)
    {
        // __name
        // "acknowledgement":0.0,
        // "acknowledgement_expiry":0.0,
        //
        $properties = array(
            'name'                 => $object->__name,
            'environment'          => $environment,
            'label'                => $object->display_name,
            'zone'                 => $object->zone,
            'package'              => $object->package,
            'groups'               => $object->groups,
            'enable_notifications' => $object->enable_notifications,
        );

        $self = static::create($properties, $environment->getConnection());
        $self->calculatePropertiesChecksum();
        $self->calculateVarsChecksum();

        return $self;
    }

    protected function calculatePropertiesChecksum()
    {
        // TODO: redefine this. Should name be part of properties?
        $parts = [];
        $keys = [
            'label',
            'name',
        ];
        foreach ($keys as $key) {
            $parts[] = sha1($this->get($key), true);
        }

        $this->set(
            'properties_checksum',
            sha1(implode(';', $parts), true)
        );
    }

    protected function calculateVarsChecksum()
    {
        $this->set('vars_checksum', sha1('', true));
    }
}
