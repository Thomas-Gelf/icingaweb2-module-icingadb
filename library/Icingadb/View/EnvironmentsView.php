<?php

namespace Icinga\Module\Icingadb\View;

class EnvironmentsView extends ListView
{
    public function getAvailableColumns()
    {
        return array(
        );
    }

    public function getColumns()
    {
        return [
            'name'          => 'e.name',
            'name_checksum' => 'e.name_checksum',
            'director_db'   => 'e.director_db',
        ];
    }

    protected function prepareBaseQuery()
    {
        $query = $this->db()
            ->select()
            ->from(
                ['e' => 'icinga_environment'],
                []
            )
            ->order('name DESC')
            ->limit(25);

        return $query;
    }
}
