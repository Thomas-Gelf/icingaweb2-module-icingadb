<?php

namespace Icinga\Module\Icingadb\Web\Component;

use ipl\Html\BaseElement;
use ipl\Html\Element;
use ipl\Html\Icon;
use ipl\Html\Link;
use ipl\Html\Table;
use Icinga\Module\Icingadb\Db\StateSummary\ServiceStateSummary;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;
use Icinga\Module\Icingadb\IcingaStateObject\HostState;
use Icinga\Module\Icingadb\IcingaStateObject\HostStateVolatile;
use Icinga\Module\Icingadb\IcingaConfigObject\IcingaHostConfig;

class HostDetails extends BaseElement
{
    protected $contentSeparator = "\n";

    /** @var string */
    protected $tag = 'div';

    protected $env;

    public function __construct(IcingaEnvironment $env, IcingaHostConfig $host, HostState $state, HostStateVolatile $volatile)
    {
        $this->env = $env;
        $this->add(
            Element::create('h2')->setContent('Plugin Output')
        )->add(
            Element::create('pre')->setContent($volatile->output)
        )->add(
            Element::create('h2')->setContent('Problem handling')
        )->add(
            Element::create('h2')->setContent('Configuration')
        )->add(
            $this->getProblemHandlingTable($host)
        )->add(
            $this->getServiceStateSummary($host)
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

    protected function getServiceStateSummary(IcingaHostConfig $host)
    {
        $table = new Table();
        $summary = new ServiceStateSummaryBadges(
            ServiceStateSummary::forHostConfig($host)
        );
        $table->add(
            Element::create('tr')->add(
                Element::create('th')->setContent('Services')
            )->add(
                Element::create(
                    'td',
                    ['data-base-target' => '_next']
                )->setSeparator(', ')->addContent($summary)
            )
        );

        return $table;
    }

    /**
     * TODO: provide a related table implementation
     *
     * @param IcingaHostConfig $host
     * @return Table
     */
    protected function getProblemHandlingTable(IcingaHostConfig $host)
    {
        $table = new Table();
        $groups = [];
        $envName = $this->env->get('name');
        foreach ($host->enumGroups() as $group => $label) {
            $groups[] = Link::create(
                $label,
                'icingadb/hostgroup',
                [
                    'name' => $group,
                    'env'  => $envName,
                ]
            );
        }

        $addLink = Link::create(Icon::create('plus'), 'director/host/addgroup');

        $table->add(
            Element::create('tr')->add(
                Element::create('th')->setContent([
                    'Hostgroups',
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
