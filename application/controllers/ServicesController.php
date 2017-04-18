<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Db\StateSummary\ServiceStateSummary;
use Icinga\Module\Icingadb\Web\Component\ServiceActionsHelper;
use Icinga\Module\Icingadb\Web\Component\ServiceTableHelper;
use Icinga\Module\Icingadb\Web\Component\ServiceStateSummaryBadges;
use Icinga\Module\Icingadb\Web\Controller;

class ServicesController extends Controller
{
    use ServiceActionsHelper;
    use ServiceTableHelper;

    public function indexAction()
    {
        $this->handleQuickActions();
        $this->setAutorefreshInterval(10);
        $title = $this->translate('Services');
        $this->singleTab($title);
        $this->controls()->add(
            new ServiceStateSummaryBadges(ServiceStateSummary::fromDb($this->ddo()))
        );
        $this->addTitle($title);
        $this->content()->add($this->getServicesTable());
    }

    public function summaryAction()
    {
        $this->content()->add(
            new ServiceStateSummaryBadges(ServiceStateSummary::fromDb($this->ddo()))
        );
    }
}
