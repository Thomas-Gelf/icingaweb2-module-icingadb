<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;
use Icinga\Module\Icingadb\IcingaStateObject\ServiceState;
use Icinga\Module\Icingadb\IcingaStateObject\ServiceStateVolatile;
use Icinga\Module\Icingadb\IcingaConfigObject\IcingaServiceConfig;
use Icinga\Module\Icingadb\Web\Component\ServiceActionBar;
use Icinga\Module\Icingadb\Web\Component\ServiceDetails;
use Icinga\Module\Icingadb\Web\Component\ServiceHeader;
use Icinga\Module\Icingadb\Web\Controller;

class ServiceController extends Controller
{
    public function showAction()
    {
        $hostName = $this->params->get('host');
        $name = $this->params->get('name');
        $db = $this->icingaDb();
        $env = IcingaEnvironment::load($this->params->get('env'), $db);
        $checksum = $env->generateGlobalChecksum("$hostName!$name");
        $service = IcingaServiceConfig::load($checksum, $db);
        $state = ServiceState::load($checksum, $db);
        $volatile = ServiceStateVolatile::failSafeFromRedis($this->redis(), $checksum);

        $this->setAutorefreshInterval(10);
        $this->controls()->attributes()->add('class', 'controls-separated');
        $this->addSingleTab($this->translate('Host'));
        $this->addTitle($name);

        $this->controls()
            ->add(new ServiceActionBar($service, $state))
            ->add(new ServiceHeader($service, $state));

        $this->content()
            ->add(new ServiceDetails($env, $service, $state, $volatile));
    }
}
