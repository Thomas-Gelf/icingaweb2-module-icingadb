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
        $db = $this->icingaDb();
        $this->setAutorefreshInterval(10);
        $this->addSingleTab($this->translate('Environments'));
        $this->addTitle($this->translate('All your Icinga Environments'));
        $this->content()->add($table = new EnvironmentsTable());
        $table->renderEnvironments(IcingaEnvironment::loadAll($db));
        $this->content()->add(
            Element::create('h1')->setContent('Add a new one:')
        )->add(
            (new IcingaEnvironmentForm())
                ->setIcingaDb($db)
                ->handleRequest()
        );
    }
}
