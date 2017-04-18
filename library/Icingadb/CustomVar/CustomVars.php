<?php

namespace Icinga\Module\Icingadb\CustomVar;

use Icinga\Module\Icingadb\IcingaDb;

class CustomVars
{
    protected $json;

    /** @var string binary SHA1 checksum */
    protected $checksum;

    /** @var CustomVar[] */
    protected $singleVars = [];

    /**
     * @return CustomVar[]
     */
    public function getSingleVars()
    {
        return $this->singleVars;
    }

    public function add(CustomVar $var)
    {
        // TODO: Do we need a list of vars?
        // $this->vars[] = $var;
    }
    /**
     * @return string
     */
    public function getChecksum()
    {
        if ($this->checksum === null) {
            $this->checksum = $this->calculateChecksum();
        }

        return $this->checksum;
    }

    /**
     * @return array
     */
    public function getChecksums()
    {
        $sums = [];
        foreach ($this->getSingleVars() as $key => $var) {
            $sums[$key] = $var->getChecksum();
        }

        return $sums;
    }

    public function storeToDb(IcingaDb $connection)
    {
        $db = $connection->getDbAdapter();
        foreach ($this->getChecksummedUnstoredVarnames($connection) as $checksum => $varname) {
            $db->insert('custom_var', [
                'checksum' => $checksum,
                'varname'  => $varname,
                'varvalue' => $this->singleVars[$varname]->toJson()
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

    public function calculateChecksum()
    {
        $sums = [];
        foreach ($this->getSingleVars() as $key => $var) {
            $sums[] = sha1("$key=". $var->getChecksum(), true);
        }

        return sha1(implode(';', $sums), true);
    }

    public function toPlainObject()
    {
        $plain = [];
        foreach ($this->getSingleVars() as $key => $var)
        {
            $plain[$key] = $var->toPlainObject();
        }

        return $plain;
    }

    public function toJson()
    {
        if ($this->json === null) {
            $this->json = json_encode($this->toPlainObject());
        }

        return $this->json;
    }
}