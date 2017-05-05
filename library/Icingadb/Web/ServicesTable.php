<?php

namespace Icinga\Module\Icingadb\Web;

use Icinga\Date\DateFormatter;
use ipl\Html\Element;
use ipl\Html\Link;
use ipl\Html\Table;
use ipl\Web\Url;

class ServicesTable extends Table
{
    protected $stateClasses = array(
        0 => 'ok',
        1 => 'warning',
        2 => 'critical',
        3 => 'unknown',
        99 => 'pending',
    );

    protected $stateNames = array(
        0 => 'OK',
        1 => 'WARNING',
        2 => 'CRITICAL',
        3 => 'UNKNOWN',
        99 => 'PENDING',
    );

    /** @inheritdoc */
    protected $defaultAttributes = array(
        'class' => array(
            'simple',
            'common-table',
            'table-row-selectable',
            'state-table',
        ),
        'data-base-target' => '_next',
    );

    /** @var Url */
    protected $baseUrl;

    public function setBaseUrl(Url $url)
    {
        $this->baseUrl = $url;
    }

    public function getColumnsToBeRendered()
    {
        return array('renderedState', 'service');
    }

    public function renderStateColumn($row)
    {
        $row->renderedState = Element::create(
            'span',
            array('class' => 'state')
        )->addContent(
            $this->stateNames[$row->state]
        )->add(
            Element::create(
                'span',
                array('class' => array('relative-time', 'time-since'))
            )->setContent(
                DateFormatter::timeSince(
                    $row->last_state_change / 1000000,
                    true
                )
            )
        );
    }

    public function addServiceLink($row)
    {
        $hostname = $row->host;
        $service = $row->service;
        $row->service = [$hostname];
        $params = [
            'host' => $hostname,
            'name' => $service,
            'env'  => $row->environment
        ];

        $row->service[] = Link::create(
            [
                $service,
                Element::create('smaller')->setContent(
                    sprintf('(%s)', $hostname)
                )
            ],
            'icingadb/service/show',
            $params
        );

        $this->addRescheduleLink($row, $hostname, $service);
        $this->addAckLink($row, $hostname, $service);

        if (property_exists($row, 'output') && !empty($row->output)) {
            $this->addServiceOutput($row, $hostname, $service);
        }
    }

    protected function addRescheduleLink($row, $hostname, $service)
    {
        $row->service[] = ' ';
        $row->service[] = Link::create(
            'Check now',
            $this->baseUrl->with([
                'action' => 'checkNow',
                'host'   => $hostname,
                'service'   => $service,
            ]),
            null,
            array(
                'class' => 'icon-reschedule',
                'data-base-target' => '_self',
            )
        );
    }

    protected function addAckLink($row, $hostname, $service)
    {
        if ($row->problem === 'n' || (int) $row->state === 99) {
            return;
        }
        if ($row->acknowledged === 'y') {
            $action = 'removeAck';
            $title = 'Remove Ack';
        } else {
            $action = 'ack';
            $title = 'Ack';
        }
        $row->service[] = ' ';
        $row->service[] = Link::create(
            $title,
            $this->baseUrl->with([
                'action'  => $action,
                'host'    => $hostname,
                'service' => $service,
            ]),
            null,
            array(
                'class' => 'icon-edit',
                'data-base-target' => '_self',
            )
        );
    }

    protected function addServiceOutput($row, $hostname, $service)
    {
        $row->service[] = Element::create(
            'p',
            array('class' => 'overview-plugin-output')
        )->setContent($row->output);
    }

    public function getRowClasses($row)
    {
        $classes = array($this->stateClasses[$row->state]);

        if ($row->problem === 'y') {
            $classes[] = 'problem';
        }

        if ($row->acknowledged === 'y') {
            $classes[] = 'handled';
            $classes[] = 'acknowledged';
        }

        if ($row->in_downtime === 'y') {
            $classes[] = 'handled';
            $classes[] = 'in_downtime';
        }

        return $classes;
    }
}
