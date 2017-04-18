<?php

namespace Icinga\Module\Icingadb\Web;

use Icinga\Module\Icingadb\Db\StateSummary\ServiceStateSummary;
use Icinga\Module\Icingadb\Web\Component\ServiceStateSummaryBadges;
use ipl\Html\Link;
use ipl\Web\Url;
use Icinga\Module\Icingadb\Db\StateSummary\HostStateSummary;
use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;
use Icinga\Module\Icingadb\Web\Component\HostStateSummaryBadges;
use ipl\Html\Table;

class EnvironmentsTable extends Table
{
    /** @inheritdoc */
    protected $defaultAttributes = [
        'class' => [ 'simple', 'common-table', 'table-row-selectable' ],
        'data-base-target' => '_next',
    ];

    public function getColumnsToBeRendered()
    {
        return [
            'environment',
            'hostSummary',
            'serviceSummary'
        ];
    }

    /**
     * @param IcingaEnvironment[] $envs
     * @return $this
     */
    public function renderEnvironments($envs)
    {
        $this->header();
        $body = $this->body();
        foreach ($envs as $env) {
            $body->add($this->renderEnvironment($env));
        }

        return $this;
    }

    protected function renderEnvironment(IcingaEnvironment $env)
    {
        $name = $env->get('name');
        $url = Url::fromPath('icingadb/hosts', ['env' => $name]);
        return $this->renderRow((object) [
            'environment'  => Link::create($name, $url),
            'hostSummary' => new HostStateSummaryBadges(
                HostStateSummary::fromDb($env->getConnection())
                    ->setEnvironmentChecksum($env->getNameChecksum())
            ),
            'serviceSummary' => new ServiceStateSummaryBadges(
                ServiceStateSummary::fromDb($env->getConnection())
                    ->setEnvironmentChecksum($env->getNameChecksum())
            ),
        ]);
    }
}
