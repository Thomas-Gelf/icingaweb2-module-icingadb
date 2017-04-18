<?php

namespace Icinga\Module\Icingadb\CustomVar;

class CustomVarSet
{
    protected $checksum;

    protected $vars = [];

    public function __construct($checksum, $vars = null)
    {
        $this->checksum = $checksum;
    }

}
