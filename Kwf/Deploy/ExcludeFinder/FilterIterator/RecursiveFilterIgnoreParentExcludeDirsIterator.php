<?php
namespace Kwf\Deploy\ExcludeFinder\FilterIterator;

use Kwf\Deploy\ExcludeFinder;

class RecursiveFilterIgnoreParentExcludeDirsIterator extends \RecursiveFilterIterator
{
    public function accept() {
        $parent = new \SplFileInfo($this->current()->getPathInfo());
        return !in_array(
            $parent->getFilename(),
            ExcludeFinder::$excludeDirs,
            true
        );
    }
}
