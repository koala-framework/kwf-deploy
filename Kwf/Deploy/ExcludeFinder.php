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
    );
    public static $gitignorewhiteList = array(
        'vendor', 'build'
    );
    public static $additionalExcludes = array(
        'deploy.zip', 'config_section', 'config.local.ini'
    );

    private static function _findExcludeBuildPackages($package, $addToBlacklist, &$blacklist, &$whitelist)
    {
        if ($package == '.') {
            $composerFile = 'composer.json';
        } else {
            $composerFile = 'vendor/'.$package.'/composer.json';
        }
        if (!file_exists($composerFile)) return;

        $c = json_decode(file_get_contents($composerFile), true);
        if (isset($c['require'])) {
            foreach ($c['require'] as $p=>$constraint) {
                if ($p == 'php' || substr($p, 0, 4) == 'ext-') continue;
                if ($addToBlacklist || (isset($c['extra']['kwf']['exclude-production']) && in_array($p, $c['extra']['kwf']['exclude-production']))) {
                    //exclude from build
                    $blacklist[] = $p;
                    self::_findExcludeBuildPackages($p, true, $blacklist, $whitelist);
                } else {
                    $whitelist[] = $p;
                    self::_findExcludeBuildPackages($p, false, $blacklist, $whitelist);
                }
            }
        }
        if ($package == '.' && isset($c['require-dev'])) {
            foreach ($c['require-dev'] as $p=>$contraint) {
                if ($p == 'php' || substr($p, 0, 4) == 'ext-') continue;
                $blacklist[] = $p;
                if ($recurse) {
                    self::_findExcludeBuildPackages($p, true, $blacklist, $whitelist);
                }
            }
        }
    }

    public static function findExcludePackages()
    {
        $blacklist = array();
        $whitelist = array();
        self::_findExcludeBuildPackages('.', false, $blacklist, $whitelist);
        $blacklist = array_unique($blacklist);
        $whitelist = array_unique($whitelist);
        return array_diff($blacklist, $whitelist);
    }

    public static function findExcludes($directory)
    {
        $excludes = array();
        $excludePackages = self::findExcludePackages();
        foreach ($excludePackages as $p) {
            $excludes[] = 'vendor/'.$p;
        }

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
                if (substr($pattern, 0, 1) == '#') continue;
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
