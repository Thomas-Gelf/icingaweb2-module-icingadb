<?php

namespace Icinga\Module\Icingadb\CustomVar;

use Icinga\Module\Icingadb\IcingaDb;

class CustomVarBulkStore
{
    protected $connection;

    protected $vars = [];

    public function __construct(IcingaDb $connection)
    {
        $this->connection = $connection;
    }

    public function addCustomVar(CustomVar $var)
    {
        $this->vars[$var->getChecksum()] = $var;
        return $this;
    }

    public function getChecksums()
    {
        return array_keys($this->vars);
    }

    public function store()
    {
        $db = $this->connection->getDbAdapter();
        foreach ($this->getUnstoredChecksums() as $checksum) {
            $var = $this->vars[$checksum];
            $db->insert('custom_var', [
                'checksum' => $checksum,
                'varname'  => $var->getName(),
                'varvalue' => $var->getValue()->toJson(),
            ]);
        }
    }

    public function getUnstoredChecksums()
    {
        $sums = $this->getChecksums();
        if (! empty($sums)) {
            $sums = array_combine($sums, $sums);
        }
        foreach ($this->listExistingChecksums($sums) as $checksum) {
            unset($sums[$checksum]);
        }

        return array_keys($sums);
    }

    public function listExistingChecksums($checksums)
    {
        return $this->connection->getDbAdapter()->fetchCol(
            $this->prepareExistingVarsQuery($checksums)
        );
    }

    public function prepareExistingVarsQuery($checksums)
    {
        $query = $this->connection->getDbAdapter()->select()->from(
            ['cv' => 'custom_var'],
            ['checksum' => 'cv.checksum']
        )->where('cv.checksum IN (?)', $checksums);

        return $query;
    }
}
