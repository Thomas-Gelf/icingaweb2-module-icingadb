<?php

namespace Icinga\Module\Icingadb\Web\Component;

use Icinga\Date\DateFormatter;
use ipl\Html\BaseElement;
use ipl\Html\Container;
use ipl\Html\Element;
use ipl\Html\HtmlTag;
use Icinga\Module\Icingadb\IcingaStateObject\HostState;
use Icinga\Module\Icingadb\IcingaConfigObject\IcingaHostConfig;

class HostHeader extends BaseElement
{
    protected $contentSeparator = "\n";

    /** @var string */
    protected $tag = 'div';

    /** @inheritdoc */
    protected $defaultAttributes = array(
        'class' => 'object-header',
    );

    public function __construct(IcingaHostConfig $object, HostState $state)
    {
        $this->add(
            $this->createStateElement($state)
        )->addContent(
            Container::create(
                array('class' => 'header-details'),
                $this->renderHostHeaderDetails($object)
            )
        );
    }

    protected function renderHostHeaderDetails(IcingaHostConfig $host)
    {
        $name = $host->get('name');
        $label = $host->get('label');
        if ($label !== null && $label !== $name) {
            $h1 = HtmlTag::h1([
                $label,
                Element::create('small')->addContent('(' . $name . ')')
            ]);
        } else {
            $h1 = HtmlTag::h1($name);
        }

        return array(
            $h1,
            Element::create('span', array('class' => 'ipaddress'))
                ->addContent($host->get('address'))
        );
    }

    protected function createStateElement(HostState $state)
    {
        return Element::create(
            'span',
            array('class' => array_merge(array('state'), $this->getStateClasses($state)))
        )->addContent(
            strtoupper($state->getStateName())
        )->add(
            Element::create(
                'span',
                array('class' => array('relative-time', 'time-since'))
            )->setContent(
                DateFormatter::timeSince(
                    $state->get('last_state_change') / 1000000,
                    true
                )
            )
        );
    }

    protected function getStateClasses(HostState $state)
    {
        $classes = array($state->getStateName());

        if ($state->isProblem()) {
            $classes[] = 'problem';
        }

        if ($state->isAcknowledged()) {
            $classes[] = 'handled';
            $classes[] = 'acknowledged';
        }

        if ($state->isInDowntime()) {
            $classes[] = 'handled';
            $classes[] = 'in_downtime';
        }

        return $classes;
    }
}
