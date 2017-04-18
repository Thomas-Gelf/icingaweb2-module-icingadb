<?php

namespace Icinga\Module\Icingadb\IcingaApi;

use Exception;
use Icinga\Module\Icingadb\IcingaConfigObject\IcingaHostConfig;
use Icinga\Module\Icingadb\IcingaConfigObject\IcingaServiceConfig;
use Icinga\Module\Icingadb\Monitoring\PerformanceDataSet;
use Icinga\Module\Director\Core\RestApiClient;

class ActionApi
{
    protected $client;

    /** @var IcingaHostConfig */
    protected $host;

    /** @var IcingaServiceConfig */
    protected $service;

    public function __construct(RestApiClient $client)
    {
        $this->client = $client;
    }

    protected function buildFilter()
    {
        if ($this->service !== null) {
            return sprintf(
                'host.name == "%s" && service.name == "%s"',
                $this->service->get('hostname'), // TODO: escape for Icinga
                $this->service->get('name')
            );
        } else {
            return 'host.name == "' . $this->host->get('name') . '"';
        }
    }

    protected function filteredUrl($url)
    {
        return $url . '?filter=' . rawurlencode($this->buildFilter());
    }

    protected function postFiltered($url, $data = [])
    {
        $data['type'] = 'Host'; // TODO -> add to filter?
        return $this->client->post(
            $this->filteredUrl($url),
            (object) $data
        );
    }

    public function setHost(IcingaHostConfig $host)
    {
        $this->host = $host;
        return $this;
    }

    public function setService(IcingaServiceConfig $service)
    {
        $this->service = $service;
        return $this;
    }

    public function processCheckResult(
        $output,
        $exitStatus = 0,
        PerformanceDataSet $perfData = null
    ) {
        return $this->postFiltered('actions/process-check-result', [
            'exit_status'  => $exitStatus,
            'plugin_outut' => $output,
            // 'performance_data' => ,
            // 'check_command'    => ,
            // 'check_source'     => ,
        ]);
    }

    public function checkNow()
    {
        return $this->postFiltered('actions/reschedule-check');
    }

    public function acknowledge($author, $comment)
    {
        return $this->postFiltered('actions/acknowledge-problem', [
            'author'  => $author,
            'comment' => $comment
        ]);
    }

    public function removeHostAcknowledgement($host)
    {
        return $this->postFiltered('actions/remove-acknowledgement');
    }

    public function reloadNow()
    {
        try {
            $this->client->post('actions/restart-process');
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getHostOutput($host)
    {
        try {
            $object = $this->getObject($host, 'hosts');
        } catch (Exception $e) {
            return 'Unable to fetch the requested object';
        }
        if (isset($object->attrs->last_check_result)) {
            return $object->attrs->last_check_result->output;
        } else {
            return '(no check result available)';
        }
    }

    /*
    public function checkAndWaitForResult($timeout = 10)
    {
        $now = microtime(true);
        $this->checkNow();

        while (true) {
            try {
                $object = $this->getObject($host, 'hosts');
                if (isset($object->attrs->last_check_result)) {
                    $res = $object->attrs->last_check_result;
                    if ($res->execution_start > $now) {
                        return $res;
                    }
                } else {
                    // no check result available
                }
            } catch (Exception $e) {
                // Unable to fetch the requested object
                throw new IcingaException(
                    'Unable to fetch the requested host "%s"',
                    $host
                );
            }
            if (microtime(true) > ($now + $timeout)) {
                break;
            }

            usleep(150000);
        }

        return false;
    }

    public function getObject($name, $pluraltype, $attrs = array())
    {
        $params = (object) array(
        );

        if (! empty($attrs)) {
            $params->attrs = $attrs;
        }
        $url = 'objects/' . urlencode(strtolower($pluraltype)) . '/' . rawurlencode($name) . '?all_joins=1';
        $res = $this->client->get($url, $params)->getResult('name');

        // TODO: check key, throw
        return $res[$name];
    }
    */
}
