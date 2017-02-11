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
    public function setSource(string $location, array $properties) : void
    {
        $this->filename = 'temp_file';
    }

    /**
    * Override the open method so that we're using a php temp file
    *
    * @return void
    **/
    public function open() : void
    {
        if ($this->fp === null) {
            $this->fp = tmpfile();
        } else {
            throw new Exceptions\FilePointerExistsException(
                'A filepointer exists on this object, use class::close to'
                .' close the current pointer '
            );
        }
    }

    /**
     * Returns the type of the source
     *
     * @return string
    **/
    public function getType() : string
    {
        return 'stream';
    }

}
