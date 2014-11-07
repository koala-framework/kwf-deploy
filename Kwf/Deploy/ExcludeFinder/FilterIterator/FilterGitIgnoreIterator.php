<?php
namespace Kwf\Deploy\ExcludeFinder\FilterIterator;
class FilterGitIgnoreIterator extends \FilterIterator
{
    public static $FILTERS = array(
        '.gitignore',
    );

    public function accept() {
        return in_array(
            $this->current()->getFilename(),
            self::$FILTERS,
            true
        );
    }
}
