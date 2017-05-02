<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Db\StateSummary\HostStateSummary;
use Icinga\Module\Icingadb\Web\Component\HostActionsHelper;
use Icinga\Module\Icingadb\Web\Component\HostsTableHelper;
use Icinga\Module\Icingadb\Web\Component\HostStateSummaryBadges;
use Icinga\Module\Icingadb\Web\Controller;

class HostsController extends Controller
{
    use HostsTableHelper;
    use HostActionsHelper;

    public function indexAction()
    {
        $this->handleQuickActions();
        $this->setAutorefreshInterval(10);
        $title = $this->translate('Hosts');
        $this->addTitle($title);
        $this->controls()->prepend(
            new HostStateSummaryBadges(HostStateSummary::fromDb($this->icingaDb()))
        );
        $this->addSingleTab($title);
        $this->actions()->add(
            $this->createAddLink('host')
        );

        $this->content()->add($this->getHostsTable());
    }

    public function summaryAction()
    {
        $this->content()->add(
            new HostStateSummaryBadges(HostStateSummary::fromDb($this->icingaDb()))
        );
    }
}
