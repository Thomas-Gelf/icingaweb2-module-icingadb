<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Application\Benchmark;
use Icinga\Module\Icingadb\Web\Component\QuickSearch;
use Icinga\Module\Icingadb\View\HostgroupsView;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Web\HostgroupsTable;

class HostgroupsController extends Controller
{
    public function indexAction()
    {
        $this->setAutorefreshInterval(10);
        $title = $this->translate('Hostgroups');
        $this->addSingleTab($title);
        $this->addTitle($title);
        $this->content()->add($table = new HostgroupsTable());
        $view = new HostgroupsView($this->icingaDb());
        if ($search = $this->params->get('search')) {
            $view->search($search);
        }
        $this->actions()
            ->add($this->createAddLink('hostgroup'))
            ->add(new QuickSearch($this->getRequest()));
        $view->addRowObserver(array($table, 'addStateSummary'));
        Benchmark::measure('Table ready');
        $table->renderRows($view->fetchRows());
    }
}
