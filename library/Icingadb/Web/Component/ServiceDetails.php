<?php

namespace Icinga\Module\Icingadb\Web\Component;

use ipl\Html\BaseElement;
use ipl\Html\Element;
use ipl\Html\Icon;
use ipl\Html\Img;
use ipl\Html\Link;
use ipl\Html\Table;
use Icinga\Module\Icingadb\Db\StateSummary\ServiceStateSummary;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;
use Icinga\Module\Icingadb\IcingaStateObject\ServiceState;
use Icinga\Module\Icingadb\IcingaStateObject\ServiceStateVolatile;
use Icinga\Module\Icingadb\IcingaConfigObject\IcingaServiceConfig;

class ServiceDetails extends BaseElement
{
    protected $contentSeparator = "\n";

    /** @var string */
    protected $tag = 'div';

    protected $env;

    public function __construct(IcingaEnvironment $env, IcingaServiceConfig $service, ServiceState $state, ServiceStateVolatile $volatile)
    {
        $this->env = $env;
        $this->add(
            Element::create('h2')->setContent('Plugin Output')
        )->add(
            Element::create('pre')->setContent($volatile->output)
        )->add(
            Img::create('rrdstore/play/img', [
                'service'  => 'app1.example.com!Load',
                'interval' => '4hours',
                'size'     => 'smaller-wide',
                'rnd'      => time()
            ], [
                'width'  => 480,
                'height' => 130
            ])
        )->add(
            Element::create('h2')->setContent('Problem handling')
        )->add(
            Element::create('h2')->setContent('Configuration')
        )->add(
            $this->getProblemHandlingTable($service)
        )->add(
            Element::create('h2')->setContent('Notifications')
        )->add(
            Element::create('h2')->setContent('Check execution')
        )->addContent(
            ''
            /*
            Link::create(
                $host->check_command,
                'director/command',
                array('name' => $host->check_command),
                array('data-base-target' => '_next')
            )
            */
        )->add(
            Element::create('h2')->setContent('Feature Commands')
        );
    }

    /**
     * TODO: provide a related table implementation
     *
     * @param IcingaServiceConfig $host
     * @return Table
     */
    protected function getProblemHandlingTable(IcingaServiceConfig $host)
    {
        $table = new Table();
        $groups = [];
        $envName = $this->env->get('name');
        foreach ($host->enumGroups() as $group => $label) {
            $groups[] = Link::create(
                $label,
                'icingadb/servicegroup',
                [
                    'name' => $group,
                    'env'  => $envName,
                ]
            );
        }

        $addLink = Link::create(Icon::create('plus'), 'director/service/addgroup');

        $table->add(
            Element::create('tr')->add(
                Element::create('th')->setContent([
                    'Servicegroups',
                    $addLink
                ])
            )->add(
                Element::create(
                    'td',
                    ['data-base-target' => '_next']
                )->setSeparator(', ')->addContent($groups)
            )
        );

        return $table;
    }
}
