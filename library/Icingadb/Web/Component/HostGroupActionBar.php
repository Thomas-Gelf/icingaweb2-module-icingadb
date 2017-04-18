<?php

namespace Icinga\Module\Icingadb\Web\Component;

use ipl\Html\Link;
use ipl\Translation\TranslationHelper;
use ipl\Web\Component\ActionBar;
use Icinga\Module\Icingadb\IcingaConfigObject\IcingaHostGroupConfig;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;

class HostGroupActionBar extends ActionBar
{
    use TranslationHelper;

    public function __construct(IcingaHostGroupConfig $hostGroup, IcingaEnvironment $env)
    {
        $this->add(
            Link::create('Modify', 'director/hostgroup', [
                'name' => $hostGroup->get('name'),
                'env'  => $env->get('name'),
            ], [
                'class' => 'icon-edit',
                'title' => $this->translate('Edit this hostgroup')
            ])
        );

        $this->add(
            Link::create('Downtime', 'ack', null, ['class' => 'icon-plug'])
        );
    }
}
