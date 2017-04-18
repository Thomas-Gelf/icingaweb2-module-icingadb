<?php

namespace Icinga\Module\Icingadb\View;

use Icinga\Application\Benchmark;
use Icinga\Module\Icingadb\IcingaDb;
use PDO;

abstract class ListView
{
    private $ddo;

    private $db;

    /** @var \Zend_Db_Select */
    private $baseQuery;

    /** @var callable[] */
    private $rowObservers = array();

    public function __construct(IcingaDb $ddo)
    {
        $this->ddo = $ddo;
        $this->db = $ddo->getDbAdapter();
    }

    abstract public function getColumns();

    public function db()
    {
        return $this->db;
    }

    public function addRowObserver(callable $observer)
    {
        $this->rowObservers[] = $observer;
        return $this;
    }

    public function fetchRows()
    {
        Benchmark::measure('Been asked to fetch rows');

        $pdo = $this->db->getConnection();
        $query = $this->getFetchQuery();
        $sth = $pdo->prepare($query);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_OBJ)) {
            foreach ($this->rowObservers as $observer) {
                $observer($row);
            }

            yield $row;
        }
    }

    public function getFetchQuery()
    {
        $query = clone($this->baseQuery());
        // TODO: reset columns? We currently fetch *, tbl.bla, tbl.oops
        $query->columns($this->getColumns());
        return $query;
    }

    public function getCountQuery()
    {
        $query = clone($this->baseQuery());
    }

    public function limitedCountQuery()
    {
        // return $this->
    }

    /**
     * @return \Zend_Db_Select
     */
    public function baseQuery()
    {
        if ($this->baseQuery === null) {
            $this->baseQuery = $this->prepareBaseQuery();
        }

        return $this->baseQuery;
    }

    abstract protected function prepareBaseQuery();

    abstract public function getAvailableColumns();
}
