<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Application\Benchmark;
use ipl\Html\Link;
use ipl\Web\Component\ActionBar;
use Icinga\Module\Icingadb\View\HostgroupsView;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Web\HostgroupsTable;

class HostgroupsController extends Controller
{
    public function indexAction()
    {
        $this->setAutorefreshInterval(10);
        $title = $this->translate('Hostgroups');
        $this->singleTab($title);
        $this->addTitle($title);
        $this->content()->add($table = new HostgroupsTable());
        $view = new HostgroupsView($this->ddo());
        $this->controls()->add(
            (new ActionBar())->add(Link::create(
                'Add',
                'director/hostgroup/add',
                [],
                [
                    'class' => 'icon-plus',
                    'data-base-target' => '_next'
                ]
            ))
        );
        $view->addRowObserver(array($table, 'addStateSummary'));
        Benchmark::measure('Table ready');
        $table->renderRows($view->fetchRows());
    }
}
