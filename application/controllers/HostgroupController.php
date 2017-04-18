<?php

namespace Icinga\Module\Icingadb\Controllers;

use ipl\Web\Url;
use Icinga\Module\Icingadb\Db\StateSummary\HostStateSummary;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;
use Icinga\Module\Icingadb\IcingaConfigObject\IcingaHostGroupConfig;
use Icinga\Module\Icingadb\Web\Component\HostActionsHelper;
use Icinga\Module\Icingadb\Web\Component\HostGroupActionBar;
use Icinga\Module\Icingadb\Web\Component\HostGroupHeader;
use Icinga\Module\Icingadb\Web\Component\HostsTableHelper;
use Icinga\Module\Icingadb\Web\Component\HostStateSummaryBadges;
use Icinga\Module\Icingadb\Web\Controller;

class HostgroupController extends Controller
{
    use HostsTableHelper;
    use HostActionsHelper;

    public function indexAction()
    {
        $this->handleQuickActions();
        $name = $this->params->get('name');
        // Used by HostsTableHelper :-/
        $this->params->set('hostgroup', $name);
        $ddo = $this->ddo();
        $env = IcingaEnvironment::load($this->params->get('env'), $ddo);
        $checksum = $env->generateGlobalChecksum($name);
        $hostGroup = IcingaHostGroupConfig::load($checksum, $ddo);

        $this->setAutorefreshInterval(10);
        $this->singleTab($this->translate('Host Group'));
        $this->setTitle(sprintf($this->translate('Host Group: %s'), $name));

        $this->controls()->add(
            new HostStateSummaryBadges(
                HostStateSummary::fromDb($this->ddo())->setHostGroupChecksum($checksum),
                Url::fromPath('icingadb/hostgroup', [
                    'name' => $name,
                    'env'  => $env->get('name')
                ])
            )
        )->add(
            new HostGroupHeader($hostGroup)
        )->add(
            new HostGroupActionBar($hostGroup, $env)
        );
        $this->content()->add($this->getHostsTable());
    }
}
