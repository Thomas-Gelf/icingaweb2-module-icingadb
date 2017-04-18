<?php

namespace Icinga\Module\Icingadb\Redis;

use Icinga\Application\Logger;
use Icinga\Exception\ProgrammingError;
use Predis\Client as PredisClient;
use Predis\Response\ServerException;

class LuaScriptRunner
{
    /** @var PredisClient */
    protected $predis;

    /** @var string */
    protected $basedir;

    protected $files = [];

    protected $checksums = [];

    public function __construct(PredisClient $predis, $basedir = null)
    {
        $this->predis = $predis;
        if ($basedir === null) {
            $this->basedir = $this->detectBaseDir();
        } else {
            $this->basedir = $basedir;
        }
    }

    protected function detectBaseDir()
    {
        return dirname(dirname(dirname(__DIR__))) . '/application/luascripts';
    }

    public function __call($funcName, $arguments)
    {
        $keys = isset($arguments[0]) ? $arguments[0] : [];
        $args = isset($arguments[1]) ? $arguments[1] : [];
        return $this->runScript($funcName, $keys, $args);
    }

    protected function loadFile($name)
    {
        $this->assertValidFileName($name);
        $filename = sprintf($this->basedir . '/' . $name . '.lua');
        return file_get_contents($filename);
    }

    protected function assertValidFileName($name)
    {
        if (! preg_match('/^[a-z0-9]+$/i', $name)) {
            throw new ProgrammingError(
                'Trying to access invalid lua script: %s',
                $name
            );
        }
    }

    protected function redisFunc($funcName, $params)
    {
        return call_user_func_array([$this->predis, $funcName], $params);
    }

    protected function getScript($name)
    {
        if (! array_key_exists($name, $this->files)) {
            $this->files[$name] = $this->loadFile($name);
        }

        return $this->files[$name];
    }

    protected function getScriptChecksum($name)
    {
        if (! array_key_exists($name, $this->checksums)) {
            $this->checksums[$name] = sha1(
                $this->getScript($name)
            );
        }

        return $this->checksums[$name];
    }

    public function runScript($name, $keys = [], $args = [])
    {
        $checksum = $this->getScriptChecksum($name);
        $params = [$checksum, count($keys)];
        $params = array_merge($params, array_merge($keys, $args));

        try {
            $result = $this->redisFunc('evalsha', $params);
        } catch (ServerException $e) {
            if (strpos($e->getMessage(), 'NOSCRIPT') !== false) {
                Logger::info(
                    'No SCRIPT with SHA1 == %s, pushing now',
                    $checksum
                );

                $params[0] = $this->getScript($name);
                $result = $this->redisFunc('eval', $params);
            } else {
                throw $e;
            }
        }

        return $result;
    }
}
