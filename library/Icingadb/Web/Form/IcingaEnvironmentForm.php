<?php

namespace Icinga\Module\Icingadb\Web\Form;

use Icinga\Data\ResourceFactory;
use Icinga\Module\Icingadb\IcingaDb;
use Icinga\Module\Icingadb\Web\Component\Form;

class IcingaEnvironmentForm extends Form
{
    /** @var IcingaDb */
    protected $ddoDb;

    public function setup()
    {
        $this->addElement('text', 'name', [
            'label' => $this->translate('Name')
        ]);

        $this->addElement('select', 'director_db', [
            'required'      => true,
            'label'         => $this->translate('Director DB Resource'),
            'multiOptions'  => $this->optionalEnum($this->enumResources()),
        ]);
    }

    public function setIcingaDb(IcingaDb $ddo)
    {
        $this->ddoDb = $ddo;
        return $this;
    }

    public function onSuccess()
    {
        $values = $this->getValues();
        $db = $this->ddoDb->getDbAdapter();
        $db->insert('icinga_environment', [
            'name' => $values['name'],
            'name_checksum' => sha1($values['name'], true),
            'director_db' => $values['director_db'],
        ]);
    }

    protected function enumResources()
    {
        $resources = array();
        $allowed = ['mysql', 'pgsql'];

        foreach (ResourceFactory::getResourceConfigs() as $name => $resource) {
            if ($resource->get('type') === 'db'
                && in_array($resource->get('db'), $allowed)
            ) {
                $resources[$name] = $name;
            }
        }

        return $resources;
    }
}
