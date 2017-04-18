<?php

namespace Icinga\Module\Icingadb\ConfigSync;

class BulkDbCreate extends BulkDbAction
{
    public function run()
    {
        if (! $this->changes->hasCreate()) {
            return 0;
        }


    }
}
