<?php
namespace Kwf\Deploy\ExcludeFinder\FilterIterator;

use Kwf\Deploy\ExcludeFinder;

class RecursiveFilterIgnoreExcludeDirsIterator extends \RecursiveFilterIterator
{
    public function accept() {
        return !in_array(
            $this->current()->getFilename(),
            ExcludeFinder::$excludeDirs,
            true
        );
    }
}
