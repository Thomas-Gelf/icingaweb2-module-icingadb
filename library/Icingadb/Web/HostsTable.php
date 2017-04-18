<?php

namespace Icinga\Module\Icingadb\Web;

use Icinga\Date\DateFormatter;
use ipl\Html\Element;
use ipl\Html\Link;
use ipl\Html\Table;
use ipl\Web\Url;

class HostsTable extends Table
{
    protected $stateClasses = array(
        0 => 'up',
        1 => 'up', // TODO: get rid of this
        2 => 'down',
        3 => 'unreachable', // nay
        // 'unknown',
        99 => 'pending',
    );

    protected $stateNames = array(
        0 => 'UP',
        1 => 'UP', // TODO: get rid of this
        2 => 'DOWN',
        'UNREACHABLE',
        'UNKNOWN',
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
        return array('renderedState', 'host');
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

    public function addHostLink($row)
    {
        $hostname = $row->host;
        $row->host = array();

        $row->host[] = Link::create(
            $hostname,
            'icingadb/host/show',
            [
                'name' => $hostname,
                'env'  => $row->environment
            ]
        );

        $this->addRescheduleLink($row, $hostname);
        $this->addAckLink($row, $hostname);

        if (property_exists($row, 'output') && !empty($row->output)) {
            $this->addHostOutput($row, $hostname);
        }
    }

    protected function addRescheduleLink($row, $hostname)
    {
        $row->host[] = ' ';
        $row->host[] = Link::create(
            'Check now',
            $this->baseUrl->with([
                'action' => 'checkNow',
                'host'   => $hostname,
                'env'  => $row->environment,
            ]),
            null,
            array(
                'class' => 'icon-reschedule',
                'data-base-target' => '_self',
            )
        );
    }

    protected function addAckLink($row, $hostname)
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
        $row->host[] = ' ';
        $row->host[] = Link::create(
            $title,
            $this->baseUrl->with([
                'action' => $action,
                'host'   => $hostname,
                'env'  => $row->environment,
            ]),
            null,
            array(
                'class' => 'icon-edit',
                'data-base-target' => '_self',
            )
        );
    }

    protected function addHostOutput($row, $hostname)
    {
        $row->host[] = Element::create(
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
