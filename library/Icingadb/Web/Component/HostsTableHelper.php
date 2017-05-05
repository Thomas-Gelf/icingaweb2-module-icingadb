<?php

namespace Icinga\Module\Icingadb\Web\Component;

use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\IcingaStateObject\HostStateVolatile;
use Icinga\Module\Icingadb\IcingaStateObject\StateObject;
use Icinga\Module\Icingadb\View\HostsView;
use Icinga\Module\Icingadb\Web\HostsTable;

trait HostsTableHelper
{
    protected function getHostsTable()
    {
        /** @var Controller $this */

        $params = $this->params;

        HostStateVolatile::setRedis($this->redis());
        $envName = $params->get('env');
        $table = new HostsTable();
        $view = new HostsView($this->icingaDB());

        $preserveParams = array('state', 'handled', 'hostgroup');
        $preserve = [];
        foreach ($preserveParams as $key) {
            $value = $params->get($key);
            if ($value !== null) {
                $preserve[$key] = $value;
            }
        }

        $table->setBaseUrl($this->url()->without([
            'handled',
            'state'
        ]));

        $query = $view->baseQuery();
        if ($envName) {
            $query->where('h.env_checksum = ?', sha1($envName, true));
        }

        if ($state = $params->get('state')) {
            $query->where(
                'state = ?',
                StateObject::getStateForName($state)
            );
        }

        if ($handled = $params->get('handled')) {
            if ($handled === 'y') {
                $query
                    ->where('acknowledged = ?', $handled)
                    ->orWhere('in_downtime = ?', $handled);
            } else {
                $query
                    ->where('acknowledged = ?', $handled)
                    ->where('in_downtime = ?', $handled);
            }
        }

        if ($hostgroup = $params->get('hostgroup')) {
            $sub = $view->db()->select()->from(
                array('hgm' => 'hostgroup_member'),
                array('e' => '(1)')
            )->where('h.global_checksum = hgm.global_host_checksum')
                ->where('hgm.global_hostgroup_checksum = ?', sha1(
                        sha1($envName, true) . sha1($hostgroup, true),
                        true
                    )
                );

            $query->where('EXISTS ?', $sub);
        }

        if ($search = $params->get('search')) {
            $query->where('h.name LIKE ?', "%$search%");
        }

        $view->addRowObserver('Icinga\\Module\\Icingadb\\IcingaStateObject\\HostStateVolatile::enrichRow');
        $view->addRowObserver(array($table, 'addHostLink'));
        $view->addRowObserver(array($table, 'renderStateColumn'));
        $table->renderRows($view->fetchRows());
        return $table;
    }
}
