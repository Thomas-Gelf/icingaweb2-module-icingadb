<?php

namespace Icinga\Module\Icingadb\Web\Component;

use Icinga\Web\Notification;
use Exception;

trait HostActionsHelper
{
    protected function handleQuickActions()
    {
        // TODO: Move this elsewhere
        $action = $this->params->shift('action');
        $hostname = $this->params->get('host');
        if ($action === 'checkNow') {
            try {
                $res = $this->api()->checkHostAndWaitForResult($hostname, 2);
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
                $this->api()->acknowledgeHostProblem(
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
                $this->api()->removeHostAcknowledgement($hostname);
            } catch (Exception $e) {
                Notification::error(sprintf(
                    'Failed to remove this acknowledgement: %s',
                    $e->getMessage()
                ));
            }
        }

        if ($action) {
            $this->redirectNow($this->getRequest()->getUrl()->without(['action', 'host']));
        }
    }
}
