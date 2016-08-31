<?php
/**
 * CSVOutput Handler
 *
 * @package Data_Cruncher
 * @subpackage Helpers
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
namespace mfmbarber\Data_Cruncher\Helpers;

use mfmbarber\Data_Cruncher\Exceptions;

class CSVOutput extends CSVFile
{
    /**
    * Override the setSource method so we're using a temp file
    * by default these have read/write states
    **/
    public function setSource($location, array $properties)
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
}
