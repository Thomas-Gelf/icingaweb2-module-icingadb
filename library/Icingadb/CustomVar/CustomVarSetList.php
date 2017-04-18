<?php

namespace Icinga\Module\Icingadb\CustomVar;

use Icinga\Module\Icingadb\IcingaDb;

class CustomVarSet
{
    protected $sets;

    /**
     * CustomVarSet constructor.
     * @param $sets array Key is the checksum, value a key/value set of vars
     */
    public function __construct($sets)
    {
        $this->sets = $sets;
    }

    public function getChecksums()
    {
        return array_keys($this->sets);
    }

    public function storeToDb(IcingaDb $connection)
    {
        $db = $connection->getDbAdapter();
        // Unfinished
        foreach ($this->getChecksummedUnstoredVarnames($connection) as $checksum => $varname) {
            $db->insert('custom_var_set', [
                'set_checksum' => $checksum,
                'var_checksum'  => $varname,
            ]);
        }
    }

    public function getChecksummedUnstoredVarnames(IcingaDb $connection)
    {
        $sums = $this->getChecksums();
        foreach ($this->listExistingChecksums($connection, $sums) as $checksum) {
            unset($sums[$checksum]);
        }

        return $sums;
    }

    public function listExistingChecksums(IcingaDb $connection, $checksums)
    {
        return $connection->getDbAdapter()->fetchCol(
            $this->prepareExistingVarsQuery($connection, $checksums)
        );
    }

    public function prepareExistingVarsQuery(IcingaDb $connection, $checksums)
    {
        $query = $connection->getDbAdapter()->select()->from(
            ['cv' => 'customvar'],
            ['checksum' => 'cv.checksum']
        )->where('checksum IN (?)', $checksums);

        return $query;
    }
}
