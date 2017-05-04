<?php

namespace Icinga\Module\Icingadb\IcingaConfigObject;

use Icinga\Data\Db\DbConnection;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;

// TODO(el): Respect ctime and mtime columns w/o influencing the hasBeenModified magic

class IcingaHostConfig extends IcingaConfigObject
{
    use IcingaConfigObjectGroups;

    //** @inheritdoc */
    // protected $timestamps = array('ctime', 'mtime');

    /** @inheritdoc */
    protected $table = 'host_config';

    /** @inheritdoc */
    protected $shortTableName = 'host';

    /** @inheritdoc */
    protected $defaultProperties = array(
        'global_checksum'           => null,
        'name_checksum'             => null,
        'env_checksum'              => null,
        'package_checksum'          => null,
        'zone_checksum'             => null,
        'properties_checksum'       => null,
        'groups_checksum'           => null,
        'vars_checksum'             => null,
        'check_command_checksum'    => null,
        'event_command_checksum'    => null,
        'check_period_checksum'     => null,
        'check_interval'            => null,
        'check_retry_interval'      => null,
        'action_url_checksum'       => null,
        'notes_url_checksum'        => null,
        'last_comment_checksum'     => null,
        'name'                      => null,
        'name_ci'                   => null,
        'label'                     => null,
        'address'                   => null,
        'address6'                  => null,
        'address_bin'               => null,
        'address6_bin'              => null,
        'active_checks_enabled'     => null,
        'event_handler_enabled'     => null,
        'flapping_enabled'          => null,
        'notifications_enabled'     => null,
        'passive_checks_enabled'    => null,
        'perfdata_enabled'          => null,
        'volatile'                  => null,
    );

    public static function fromIcingaObject($object, IcingaEnvironment $environment)
    {
        // __name
        // "acknowledgement":0.0,
        // "acknowledgement_expiry":0.0,
        //
        $properties = array(
            'name'                      => $object->__name,
            'environment'               => $environment,
            'label'                     => $object->display_name,
            'groups'                    => $object->groups,
            'action_url_checksum'       => sha1($object->action_url, true),
            'notes_url_checksum'        => sha1($object->notes_url, true),
            'address'                   => $object->address,
            'address6'                  => $object->address6,
            'address_bin'               => $object->address,
            'address6_bin'              => $object->address6,
            'active_checks_enabled'     => $object->enable_active_checks,
            'event_handler_enabled'     => $object->enable_event_handler,
            'flapping_enabled'          => $object->enable_flapping,
            'notifications_enabled'     => $object->enable_notifications,
            'passive_checks_enabled'    => $object->enable_passive_checks,
            'perfdata_enabled'          => $object->enable_perfdata,
            'volatile'                  => $object->volatile,
            'zone'                      => $object->zone,
            'package'                   => $object->package,
            'check_command_checksum'    => sha1($object->check_command, true),
            'event_command_checksum'    => sha1($object->event_command, true),
            'check_interval'            => $object->check_interval,
            'check_period_checksum'     => sha1($object->check_period, true),
            // 'max_check_attempts'        => $object->max_check_attempts,
            // 'check_timeout'             => $object->check_timeout,
            'check_retry_interval'      => $object->retry_interval,
            // command_endpoint          => $object->command_endpoint,
            // icon_image, icon_image_alt, notes, notes_url":"",
            // templates: [ "test594.example.com", "Random Fortune", "Default Host" ],
        );

        $host = static::create($properties, $environment->getConnection());
        $host->calculatePropertiesChecksum();
        $host->calculateVarsChecksum();

        return $host;
    }

    protected function calculatePropertiesChecksum()
    {
        $parts = [];
        $keys = [
            'action_url_checksum',
            'active_checks_enabled',
            'address',
            'address6',
            'check_command_checksum',
            'check_interval',
            'check_retry_interval',
            'event_handler_enabled',
            'flapping_enabled',
            'label',
            'name',
            'notes_url_checksum',
            'notifications_enabled',
            'passive_checks_enabled',
            'perfdata_enabled',
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

    public function getAddressBin()
    {
        $value = $this->properties['address_bin'];
        if ($value !== null) {
            $value = inet_ntop($value);
        }

        return $value;
    }

    public function setAddressBin($address)
    {
        if (! empty($address)) {
            $value = @inet_pton($address);
            if ($value === false) {
                $value = null;
            }
        } else {
            $value = null;
        }

        return $this->reallySet('address_bin', $value);
    }

    public function getAddress6Bin()
    {
        $value = $this->properties['address6_bin'];
        if ($value !== null) {
            $value = inet_ntop($value);
        }

        return $value;
    }

    public function setAddress6Bin($address6)
    {
        if (! empty($address6)) {
            $value = @inet_pton($address6);
            if ($value === false) {
                $value = null;
            }
        } else {
            $value = null;
        }

        return $this->reallySet('address6_bin', $value);
    }
}
