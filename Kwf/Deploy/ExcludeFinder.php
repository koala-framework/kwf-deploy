<?php
namespace Kwf\Deploy;

use Kwf\Deploy\ExcludeFinder\FilterIterator\RecursiveFilterIgnoreExcludeDirsIterator;
use Kwf\Deploy\ExcludeFinder\FilterIterator\RecursiveFilterIgnoreParentExcludeDirsIterator;
use Kwf\Deploy\ExcludeFinder\FilterIterator\FilterExcludeDirsIterator;
use Kwf\Deploy\ExcludeFinder\FilterIterator\FilterGitIgnoreIterator;

class ExcludeFinder
{
    public static $excludeDirs = array(
        '.git',
        'node_modules',
        'tests',
        'test',
        'doc',
        'docs',
        'docs_src',
        'examples',
    );
    public static $gitignorewhiteList = array(
        'vendor', 'build'
    );
    public static $additionalExcludes = array(
        'deploy.tar.gz', 'config_section', 'config.local.ini'
    );

    public static function findExcludes($directory)
    {
        $excludes = array();
        $it = new \RecursiveDirectoryIterator($directory);
        $it = new RecursiveFilterIgnoreParentExcludeDirsIterator($it);
        $it = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::SELF_FIRST);
        $it = new FilterExcludeDirsIterator($it);
        foreach ($it as $i) {
            $excludes[] = $i->__toString();
        }

        $it = new \RecursiveDirectoryIterator($directory);
        $it = new RecursiveFilterIgnoreExcludeDirsIterator($it);
        $it = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::SELF_FIRST);
        $it = new FilterGitIgnoreIterator($it);
        foreach ($it as $filePath => $fileInfo) {
            foreach (file($fileInfo->__toString()) as $pattern) {
                if (substr($pattern, 0, 1) == '!') continue;
                if (!trim($pattern)) continue;
                if (substr($pattern, 0, 1) == '/') $pattern = substr($pattern, 1);
                $excludes[] = substr($fileInfo->__toString(), 0, -10).trim($pattern);
            }
        }
        foreach ($excludes as $k=>$i) {
            if (substr($i, 0, 2) == './') {
                $excludes[$k] = substr($i, 2);
            }
        }

        $excludes = array_merge($excludes, self::$additionalExcludes);
        $excludes = array_diff($excludes, self::$gitignorewhiteList);
        $excludes = array_unique($excludes);

        return $excludes;
    }
}