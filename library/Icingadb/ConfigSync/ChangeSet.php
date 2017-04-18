<?php

namespace Icinga\Module\Icingadb\ConfigSync;

class ChangeSet
{
    protected $countOld;

    protected $countNew;

    /** @var ChangeSetObjects */
    protected $create;

    /** @var ChangeSetObjects */
    protected $modify;

    /** @var ChangeSetObjects */
    protected $delete;

    public function __construct()
    {
        $this->create = new ChangeSetObjects();
        $this->modify = new ChangeSetObjects();
        $this->delete = new ChangeSetObjects();
    }

    public function isEmpty()
    {
        return $this->create->isEmpty()
            && $this->modify->isEmpty()
            && $this->delete->isEmpty();
    }

    public function setCountOld($count)
    {
        $this->countOld = $count;
        return $this;
    }

    public function setCountNew($count)
    {
        $this->countNew = $count;
        return $this;
    }

    public function create($key, $value)
    {
        $this->create->set($key, $value);
        return $this;
    }

    public function modify($key, $value)
    {
        $this->modify->set($key, $value);
        return $this;
    }

    public function delete($key, $value)
    {
        $this->delete->set($key, $value);
        return $this;
    }

    public function hasCreate()
    {
        return ! $this->create->isEmpty();
    }

    public function hasModify()
    {
        return ! $this->modify->isEmpty();
    }

    public function hasDelete()
    {
        return ! $this->delete->isEmpty();
    }

    /**
     * @return ChangeSetObjects
     */
    public function getCreated()
    {
        return $this->create;
    }

    /**
     * @return ChangeSetObjects
     */
    public function getModified()
    {
        return $this->modify;
    }

    /**
     * @return ChangeSetObjects
     */
    public function getDeleted()
    {
        return $this->delete;
    }
}
