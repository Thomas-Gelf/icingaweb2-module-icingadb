<?php

namespace Icinga\Module\Icingadb\Web\Component;

use ipl\Html\Link;
use ipl\Web\Component\ActionBar;
use Icinga\Module\Icingadb\IcingaStateObject\ServiceState;
use Icinga\Module\Icingadb\IcingaConfigObject\IcingaServiceConfig;

class ServiceActionBar extends ActionBar
{
    public function __construct(IcingaServiceConfig $service, ServiceState $state)
    {
        $this->attributes()->add('data-base-target', '_self');
        $this->add(
            Link::create('Acknowledge', 'ack', null, array('class' => 'icon-edit'))
        );
        $this->add(
            Link::create('Check Now', 'ack', null, array('class' => 'icon-reschedule'))
        );
        /*
        $this->add(
            Link::create(
                'Modify',
                'director/host/edit',
                ['name' => $service->name],
                ['class' => 'icon-edit']
            )
        );
        */
        $this->add(
            Link::create('Comment', 'ack', null, array('class' => 'icon-comment-empty'))
        );
        $this->add(
            Link::create('Downtime', 'ack', null, array('class' => 'icon-plug'))
        );
    }
}
