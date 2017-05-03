<?php

namespace Icinga\Module\Icingadb\Web\Component;

use ipl\Web\Url;
use Icinga\Module\Icingadb\IcingaStateObject\HostStateVolatile;
use Icinga\Module\Icingadb\IcingaStateObject\StateObject;
use Icinga\Module\Icingadb\View\ServicesView;
use Icinga\Module\Icingadb\Web\ServicesTable;

trait ServiceTableHelper
{
    protected function getServicesTable()
    {
        $params = $this->params;

        HostStateVolatile::setRedis($this->redis());
        $envName = $params->get('env');
        $table = new ServicesTable();
        $view = new ServicesView($this->icingaDB());

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
            $query->where('s.env_checksum = ?', sha1($envName, true));
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

        if ($servicegroup = $params->get('servicegroup')) {
            $sub = $view->db()->select()->from(
                array('sgm' => 'servicegroup_member'),
                array('e' => '(1)')
            )->where('s.global_checksum = sgm.global_service_checksum')
                ->where('sgm.global_servicegroup_checksum = ?', sha1(
                        sha1($envName, true) . sha1($servicegroup, true),
                        true
                    )
                );

            $query->where('EXISTS ?', $sub);
        }
        $view->addRowObserver('Icinga\\Module\\Icingadb\\IcingaStateObject\\ServiceStateVolatile::enrichRow');
        $view->addRowObserver(array($table, 'addServiceLink'));
        $view->addRowObserver(array($table, 'renderStateColumn'));
        $table->renderRows($view->fetchRows());
        return $table;
    }
}