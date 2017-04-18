<?php

namespace Icinga\Module\Icingadb\Controllers;

use ipl\Html\Element;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Web\EnvironmentsTable;
use Icinga\Module\Icingadb\Web\Form\IcingaEnvironmentForm;

class EnvironmentsController extends Controller
{
    public function indexAction()
    {
        $this->setAutorefreshInterval(10);
        $title = $this->translate('Environments');
        $this->singleTab($title);
        $this->addTitle($title);
        $this->content()->add($table = new EnvironmentsTable());
        $table->renderEnvironments(IcingaEnvironment::loadAll($this->ddo()));
        $this->content()->add(
            Element::create('h1')->setContent('Add a new one:')
        )->add(
            (new IcingaEnvironmentForm())
                ->setIcingaDb($this->ddo())
                ->handleRequest()
        );
    }
}
