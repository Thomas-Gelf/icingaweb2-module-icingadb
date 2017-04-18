<?php

namespace Icinga\Module\Icingadb\Db\StateSummary;

use Icinga\Data\Filter\Filter;
use Icinga\Exception\ProgrammingError;
use Icinga\Module\Icingadb\IcingaDb;
use stdClass;

class StateSummary
{
    /** @var IcingaDb */
    protected $db;

    protected $data;

    protected $filter;

    protected $table;

    /** @var string */
    protected $envChecksum;

    protected function __construct()
    {
    }

    public static function fromDb(IcingaDb $db)
    {
        $summary = new static();
        $summary->db = $db;
        return $summary;
    }

    public function setFilter(Filter $filter)
    {
        $this->filter = $filter;
        throw new ProgrammingError('Not yet');
    }

    public function fetch()
    {
        if ($this->data === null) {
            $this->data = $this->fetchDataFromDb();
        }

        return $this->data;
    }

    public function setEnvironmentChecksum($checksum)
    {
        $this->envChecksum = $checksum;
        return $this;
    }

    public static function forCombinedDbRow(stdClass $row)
    {
        $data = array();
        foreach ((array) $row as $key => $val) {
            if ((int) $val === 0) {
                continue;
            }

            if (substr($key, 0, 4) === 'cnt_') {
                $num = substr($key, 4);
                if (ctype_digit($num)) {
                    $data[(int) $num] = $val;
                }
            }
        }

        krsort($data);
        $summary = new static();
        $summary->data = $data;
        return $summary;
    }

    public function getTableName()
    {
        if ($this->table === null) {
            throw new ProgrammingError('StateSummary implementation needs a table');
        }

        return $this->table;
    }

    protected function prepareQuery()
    {
        $db = $this->db->getDbAdapter();
        $query = $db->select()->from(
            array('s' => $this->getTableName()),
            array(
                'severity' => 's.severity',
                'cnt' => 'COUNT(*)',
            )
        )->group('s.severity')
            ->order('s.severity DESC');

        if ($this->envChecksum) {
            $query->where('s.env_checksum = ?', $this->envChecksum);
        }

        return $query;
    }

    protected function fetchDataFromDb()
    {
        return $this->db->getDbAdapter()->fetchPairs(
            $this->prepareQuery()
        );
    }
}
