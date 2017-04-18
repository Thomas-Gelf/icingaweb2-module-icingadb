<?php

namespace Icinga\Module\Icingadb;

use Exception;
use Icinga\Application\Icinga;
use Icinga\Data\ConfigObject;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Director\Data\Db\DbConnection;

/**
 * Class IcingaDb
 *
 * TODO: remove dependency on Director once we replaced DbObject
 *
 * @package Icinga\Module\Icingadb
 */
class IcingaDb extends DbConnection
{
    public static function fromResourceName($name)
    {
        // TODO: remove this once fixed in Icinga Web 2
        $filename = Icinga::app()->getConfigDir('resources.ini');
        $res = parse_ini_file($filename, true);
        if (! array_key_exists($name, $res)) {
            throw new ConfigurationError("There is no DB resource named $name");
        }

        return new static(new ConfigObject($res[$name]));
    }

    /**
     * Calls the given callback within in a safe transaction
     *
     * @param callable $callback
     * @throws Exception
     */
    public function runFailSafe(callable $callback)
    {
        $db = $this->getDbAdapter();
        $db->beginTransaction();

        try {
            call_user_func($callback);
            $db->commit();
        } catch (Exception $e) {
            try {
                $db->rollBack();
            } catch (Exception $e) {
            }

            throw $e;
        }
    }

    public function isPgsql()
    {
        // TODO(tg/el): Not PostgreSQL support yet
        // return $this->getDbType() === 'pgsql';
        return false;
    }
}
