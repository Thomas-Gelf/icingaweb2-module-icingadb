<?php

namespace Icinga\Module\Icingadb\IcingaConfigObject;

trait IcingaConfigObjectVars
{
    private $vars;

    private $varsAsJson;

    public function calculateVarsChecksum()
    {
        ksort($this->vars);
        return sha1(implode('', array_keys($this->groups)), true);
    }

    public function setVars($vars)
    {
        if (is_string($vars)) {
            $this->varsAsJson = $vars;
        }

        $this->set('vars_checksum', $this->calculateVarsChecksum());
    }

    public function fetchVars()
    {
        /** @var IcingaConfigObject $this */
        $db = $this->getConnection()->getDbAdapter();

        $type = $this->getShortTableName();
        $prefix = $type;

        $query = $db->select()->from(
            ['g' => $prefix . 'group'],
            [
                'group_name' => 'g.name',
                'label'      => 'g.label'
            ]
        )->join(
            ['gm' => $prefix . 'group_member'],
            'g.global_checksum = gm.global_' . $type . 'group_checksum',
            []
        )->where(
            'gm.global_' . $type . '_checksum = ?',
            $this->get('global_checksum')
        );

        return $db->fetchPairs($query);
    }

    public function getGroups()
    {
        // ????
        return $this->groups;
    }
}
