<?php
namespace Kwf\Deploy\ExcludeFinder\FilterIterator;

use Kwf\Deploy\ExcludeFinder;

class FilterExcludeDirsIterator extends \FilterIterator
{
    public function accept() {
        return in_array(
            $this->current()->getFilename(),
            ExcludeFinder::$excludeDirs,
            true
        );
    }
}
