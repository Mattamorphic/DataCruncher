<?php
/**
 * CSVOutput Handler
 *
 * @package DataCruncher
 * @subpackage Helpers
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
namespace mfmbarber\DataCruncher\Helpers\System;

use mfmbarber\DataCruncher\Exceptions;
use mfmbarber\DataCruncher\Helpers\Files\CSVFile as CSVFile;

class CSV extends CSVFile
{
    /**
    * Override the setSource method so we're using a temp file
    * by default these have read/write states
    **/
    public function setSource(string $location, array $properties)
    {
        $this->_filename = 'temp_file';
    }
    /**
    * Override the open method so that we're using a php temp file
    **/
    public function open()
    {
        if ($this->_fp === null) {
            $this->_fp = tmpfile();
        } else {
            throw new Exceptions\FilePointerExistsException(
                'A filepointer exists on this object, use class::close to'
                .' close the current pointer '
            );
        }
    }
    /**
     * Returns the type of the source
     * @return string
    **/
    public function getType() : string
    {
        return 'stream';
    }

}
