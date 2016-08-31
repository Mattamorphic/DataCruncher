<?php
/**
 * CSVFile Handler
 *
 * @package Data_Cruncher
 * @subpackage Helpers
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
namespace mfmbarber\Data_Cruncher\Helpers;

use mfmbarber\Data_Cruncher\Exceptions;

class CSVFile extends DataFile implements DataInterface
{
    private $_headers = [];
    private $_delimiter = ',';
    private $_encloser = "\"";
    
    /**
     * Opens a file at the beginning, reads a line and closes the file
     * Returns the configured fields
     * 
     * @return array
    **/
    public function getHeaders()
    {
        $this->open();
        $this->getNextDataRow();
        $this->close();
        return $this->_headers;
    }
    /**
     * Calls the _getcsv method to get the next line, if it
     * exists. The process then creates an a set of key value pairs that
     * depict that line (headers and values)
     *
     * @return array
    **/
    public function getNextDataRow()
    {
        if ([] !== ($line = $this->_getCsv())) {
            // trim all the values in the array of values
            $line = array_map('trim', $line);
            // if we are yet to get the headers, then this is the header line
            // so set it
            if ($this->_headers === []) {
                $this->_headers = $line;
                return $this->getNextDataRow();
            } else {
                return array_combine($this->_headers, $line);
            }
        } else {
            return [];
        }
    }
    public function writeDataRow(array $row)
    {
        if ($this->_fp !== null) {
            if ($this->_headers === []) {
                $this->_headers = array_keys($row);
                if (false === fputcsv($this->_fp, $this->_headers, $this->_delimiter, $this->_encloser)) {
                    throw new \RuntimeException("Couldn't write to {$this->getSourceName()}");
                }
            }
            fputcsv($this->_fp, $row, $this->_delimiter, $this->_encloser);
        } else {
            throw new Exceptions\FilePointerInvalidException(
                'The filepointer is null on this object, use class::open'
                .' to open a new filepointer'
            );
        }
    }

    public function reset()
    {
        $this->_headers = [];
        parent::reset();
    }

    /**
     * _getcsv is a private method that uses the file pointer to get the next
     * line
     *
     * @return array / bool
    **/
    private function _getCsv()
    {
        $row = fgetcsv($this->_fp, 1000, $this->_delimiter, $this->_encloser);
        return (!$row) ? [] : $row;
    }
}
