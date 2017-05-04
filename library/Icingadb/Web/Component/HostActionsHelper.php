<?php

namespace Icinga\Module\Icingadb\Web\Component;

use Icinga\Module\Director\Core\CoreApi;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Web\Notification;
use Exception;

trait HostActionsHelper
{
    protected function handleQuickActions()
    {
        /** @var Controller $this */

        // TODO: Move this elsewhere
        $action = $this->params->shift('action');
        $hostname = $this->params->get('host');
        if (! $action) {
            return;
        }

        /** @var CoreApi $api */
        $api = $this->api();
        if ($action === 'checkNow') {
            try {
                $res = $api->checkHostAndWaitForResult($hostname, 2);
                if ($res === false) {
                    Notification::warning('Scheduled a new check, got no new result yet');
                }
            } catch (\Exception $e) {
                Notification::error(sprintf(
                    'Rescheduling the check failed: %s',
                    $e->getMessage()
                ));
            }
        } elseif ($action === 'ack') {
            try {
                $api->acknowledgeHostProblem(
                    $hostname,
                    $this->Auth()->getUser()->getUsername(),
                    "I'm working on this"
                );
            } catch (Exception $e) {
                Notification::error(sprintf(
                    'Acknowledging the problem failed: %s',
                    $e->getMessage()
                ));
            }
        } elseif ($action === 'removeAck') {
            try {
                $api->removeHostAcknowledgement($hostname);
            } catch (Exception $e) {
                Notification::error(sprintf(
                    'Failed to remove this acknowledgement: %s',
                    $e->getMessage()
                ));
            }
        }

        if ($action) {
            $this->redirectNow($this->getOriginalUrl()->without(['action', 'host']));
        }
    }
}
