<?php
namespace Kwf\Deploy\Db;

use Symfony\Component\Process\Process;

class Dump
{
    public static function dump()
    {
        $ret = '';

        if (!isset(\Kwf_Setup::$configClass)) {
            throw new Exception("Run Kwf_Setup::setUp() before using this method");
        }

        $dbConfig = \Kwf_Registry::get('dao')->getDbConfig();
        $cacheTables = \Kwf_Util_ClearCache::getInstance()->getDbCacheTables();

        $dumpCmd = "mysqldump";
        $dumpCmd .= " --host=".escapeshellarg($dbConfig['host']);
        $dumpCmd .= " --user=".escapeshellarg($dbConfig['username']);
        $dumpCmd .= " --password=".escapeshellarg($dbConfig['password']);

        $cmd = $dumpCmd;
        foreach ($cacheTables as $t) {
            $cmd .=" --ignore-table=".escapeshellarg($dbConfig['dbname'].'.'.$t);
        }
        $cmd .= " $dbConfig[dbname]";
        $process = new Process($cmd);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        $ret .= $process->getOutput();

        foreach ($cacheTables as $t) {
            $cmd = $dumpCmd;
            $cmd .= " --no-data ".escapeshellarg($dbConfig['dbname'])." ".escapeshellarg($t);
            $process = new Process($cmd);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }
            $ret .= $process->getOutput();
        }

        return $ret;
    }
}
