<?php

namespace Icinga\Module\Icingadb\CustomVar;

class CustomVarNull extends CustomVarValue
{
    public function calculateChecksum()
    {
        return sha1('', true);
    }

    public function toPlainObject()
    {
        return null;
    }
}
