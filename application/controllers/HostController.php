<?php

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\IcingaEnvironment\IcingaEnvironment;
use Icinga\Module\Icingadb\IcingaStateObject\HostState;
use Icinga\Module\Icingadb\IcingaStateObject\HostStateVolatile;
use Icinga\Module\Icingadb\IcingaConfigObject\IcingaHostConfig;
use Icinga\Module\Icingadb\Web\Component\HostActionBar;
use Icinga\Module\Icingadb\Web\Component\HostDetails;
use Icinga\Module\Icingadb\Web\Component\HostHeader;
use Icinga\Module\Icingadb\Web\Controller;

class HostController extends Controller
{
    public function showAction()
    {
        $name = $this->params->get('name');
        $ddo = $this->ddo();
        $env = IcingaEnvironment::load($this->params->get('env'), $ddo);
        $checksum = $env->generateGlobalChecksum($name);
        $host = IcingaHostConfig::load($checksum, $ddo);
        $state = HostState::load($checksum, $ddo);
        $volatile = HostStateVolatile::failSafeFromRedis($this->redis(), $checksum);

        $this->setAutorefreshInterval(10);
        $this->controls()->attributes()->add('class', 'controls-separated');
        $this->singleTab($this->translate('Host'));
        $this->setTitle(sprintf($this->translate('Host: %s'), $name));

        $this->controls()
            ->add(new HostHeader($host, $state))
            ->add(new HostActionBar($host, $state));

        $this->content()
            ->add(new HostDetails($env, $host, $state, $volatile));
    }
}
