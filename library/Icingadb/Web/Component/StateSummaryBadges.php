<?php

namespace Icinga\Module\Icingadb\Web\Component;

use Icinga\Exception\ProgrammingError;
use ipl\Html\BaseElement;
use ipl\Html\Element;
use ipl\Html\Link;
use ipl\Web\Url;
use Icinga\Module\Icingadb\Db\StateSummary\StateSummary;
use Icinga\Module\Icingadb\IcingaStateObject\StateObject;

abstract class StateSummaryBadges extends BaseElement
{
    protected $contentSeparator = ' ';

    /** @var string */
    protected $tag = 'div';

    /** @inheritdoc */
    protected $defaultAttributes = array(
        'class' => 'statesummary',
    );

    /** @var string */
    protected $baseUrl;

    public function __construct(StateSummary $states, Url $baseUrl = null)
    {
        if ($baseUrl !== null) {
            $this->baseUrl = $baseUrl;
        }

        $inUse = array();
        foreach ($states->fetch() as $state => $count) {
            $stateName = $this->getStateNameFromSeverity($state);
            if (! array_key_exists($stateName, $inUse)) {
                $inUse[$stateName] = array();
            }

            if (($state & StateObject::FLAG_NONE) === StateObject::FLAG_NONE) {
                $inUse[$stateName]['unhandled'] = $count;
            } else {
                $inUse[$stateName]['handled'] = $count;
            }
        }

        foreach ($inUse as $stateName => $counts) {
            $ul = Element::create('ul', array('class' => $stateName));
            $this->addItem($ul, 'unhandled', $stateName, $counts, 'n');
            $this->addItem($ul, 'handled', $stateName, $counts, 'y', array('class' => 'handled'));
            $this->add($ul);
        }
    }

    protected function addItem(Element $ul, $key, $stateName, $counts, $handled, $attrs = null)
    {
        if (array_key_exists($key, $counts)) {
            $ul->add(
                Element::create('li', $attrs)->setContent(
                    $this->makeLink($counts[$key], $stateName, $handled)
                )
            );
        }

        return $this;
    }

    public function getBaseUrl()
    {
        if ($this->baseUrl === null) {
            throw new ProgrammingError(
                'StateSummaryBadges implementations need a baseUrl'
            );
        }

        return $this->baseUrl;
    }

    protected function makeLink($count, $stateName, $handled)
    {
        if ($this->baseUrl instanceof Url) {
            $url = $this->baseUrl->with(array(
                'state'   => $stateName,
                'handled' => $handled
            ));
        } else {
            $url = Url::fromPath($this->baseUrl, array(
                'state'   => $stateName,
                'handled' => $handled
            ));
        }
        return Link::create(
            $count,
            $url
        );
    }

    abstract public function getStateNameFromSeverity($severity);
}
