<?php

namespace Icinga\Module\Icingadb\Web;

use ipl\Html\Link;
use ipl\Web\Url;
use Icinga\Module\Icingadb\Db\StateSummary\HostStateSummary;
use Icinga\Module\Icingadb\Web\Component\HostStateSummaryBadges;
use ipl\Html\Table;

class HostgroupsTable extends Table
{
    /** @inheritdoc */
    protected $defaultAttributes = [
        'class' => [
            'simple',
            'common-table',
            'table-row-selectable',
        ],
        'data-base-target' => '_next',
    ];

    public function getColumnsToBeRendered()
    {
        return ['hostgroup', 'stateSummary'];
    }

    public function addStateSummary($row)
    {
        $name = $row->hostgroup;
        $env = $row->env_name;
        $url = Url::fromPath('icingadb/hostgroup', [
            'name' => $name,
            'env'  => $env,
        ]);
        $row->stateSummary = new HostStateSummaryBadges(
            HostStateSummary::forCombinedDbRow($row),
            $url
        );
        $row->hostgroup = Link::create(
            $name,
            $url
        );
    }
}
