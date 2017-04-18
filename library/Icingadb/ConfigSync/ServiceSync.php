<?php

namespace Icinga\Module\Icingadb\ConfigSync;

class ServiceSync extends ObjectSync
{
    /*
    protected $attrs = [
        'name',
        'host',
        'groups',
        'address',
        'address6',
        'check_command',
        'check_interval',
        'display_name',
        'enable_active_checks',
        'enable_event_handler',
        'enable_flapping',
        'enable_notifications',
        'enable_passive_checks',
        'enable_perfdata',
        'action_url',
        'notes_url',
        'retry_interval',
        // 'vars',
        // downtime_depth
        //"acknowledgement":0.0,"acknowledgement_expiry":0.0,
        // event_command
        // "icon_image":"","icon_image_alt":""
        // zone
        // volatile
    ];
    */

    public function getType()
    {
        return 'service';
    }
}
