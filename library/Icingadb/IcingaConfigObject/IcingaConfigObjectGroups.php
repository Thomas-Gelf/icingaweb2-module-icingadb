<?php

namespace Icinga\Module\Icingadb\IcingaConfigObject;

trait IcingaConfigObjectGroups
{
    private $groups = [];

    public function calculateGroupsChecksum()
    {
        ksort($this->groups);
        return sha1(implode('', array_keys($this->groups)), true);
    }

    public function setGroups(array $groupNames)
    {
        $this->groups = [];
        foreach ($groupNames as $name) {
            $key = sha1($name, true);
            $this->groups[$key] = $name;
        }

        $this->set('groups_checksum', $this->calculateGroupsChecksum());
    }

    public function enumGroups()
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
