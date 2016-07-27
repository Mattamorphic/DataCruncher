<?php

namespace mfmbarber\Data_Cruncher\Helpers;
use mfmbarber\Data_Cruncher\Exceptions;

class DataFile implements DataInterface
{
    private $_headers = [];
    private $_filename = '';
    private $_delimiter = ',';
    private $_encloser = "\"";
    private $_fp = null;

    /**
     * Sets the source  file of the Manipulator object, if valid sets attributes
     *
     * @param string $filename   The name of the file to set as the source
     * @param array  $properties Data properties, modifier, encloser, delimiter
     *
     * @return null
    **/
    public function setSource($filename, array $properties = [])
    {
        $modifier = 'r';
        if (isset($properties['modifier'])) {
            $modifier = strtolower($properties['modifier']);
        }
        if (false !== strpos('r', $modifier) && !$this->readable($filename)) {
            if (!$this->fileExists($filename)) {
                throw new Exceptions\InvalidFileException("$filename doesn't exist");
            }
            throw new Exceptions\InvalidFileException("$filename is not readable");
        }
        if (false !== strpos('w', $modifier) && !$this->writable($filename)) {
            throw new Exceptions\InvalidFileException("$filename is not writable");
        }
        $this->_filename = $filename;
        if (isset($properties['delimiter'])) {
            $this->_delimiter = $properties['delimiter'];
        }
        if (isset($properties['encloser'])) {
            $this->_encloser = $properties['encloser'];
        }
    }

    public function fileExists($filename)
    {
        return (bool) file_exists($filename);
    }

    /**
     * We abstract this method as we might change our check for writable in future
     * @param string $filename  The name of the file to check is writable
     *
     * @return bool
    **/
    public function writable($filename)
    {
        return (bool) is_writable($filename);
    }

    /**
     * We abstract this method as we might change our check for readable in future
     * @param string $filename  The name of the file to check is writable
     *
     * @return bool
    **/
    public function readable($filename)
    {
        return (bool) is_readable($filename);
    }
    /**
     * Returns the current filename tied to this object
     *
     * @return string
    **/
    public function getSourceName()
    {
        return $this->_filename;
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
    /**
     * Open is a method that sets a local file pointer
     *
     * @return null
    **/
    public function open()
    {
        if ($this->_fp === null) {
            $this->_fp = fopen($this->_filename, 'a+');

        } else {
            throw new Exceptions\FilePointerExistsException(
                'A filepointer exists on this object, use DataFile::close to'
                .' close the current pointer '
            );
        }
    }
    public function reset()
    {
        if ($this->_fp !== null) {
            rewind($this->_fp);
        } else {
            throw new Exceptions\FilePointerInvalidException(
                'The filepointer is null on this object, use DataFile::open'
                .' to open a new filepointer'
            );
        }
    }
    public function writeDataRow(array $row)
    {
        if ($this->_fp !== null) {
            fputcsv(
                $this->_fp,
                array_values($row),
                $this->_delimiter,
                $this->_encloser
            );
        } else {
            throw new Exceptions\FilePointerInvalidException(
                'The filepointer is null on this object, use DataFile::open'
                .' to open a new filepointer'
            );
        }
    }
    /**
     * Close closes the file pointer attribute
     *
     * @return null
    **/
    public function close()
    {
        if ($this->_fp !== null) {
            fclose($this->_fp);
            $this->_fp = null;
        } else {
            throw new Exceptions\FilePointerInvalidException(
                'The filepointer is null on this object, use DataFile::open'
                .' to open a new filepointer'
            );
        }
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
