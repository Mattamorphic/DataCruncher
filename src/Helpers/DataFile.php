<?php

namespace mfmbarber\Data_Cruncher\Helpers;
use mfmbarber\Data_Cruncher\Exceptions;

abstract class DataFile {
    protected $_modifier = 'r';
    protected $_fp = null;
    protected $_filename = '';
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
        if (isset($properties['modifier'])) {
            $this->_modifier = strtolower($properties['modifier']);
        }
        if (false !== strpos('r', $this->_modifier) && !$this->readable($filename)) {
            if (!$this->fileExists($filename)) {
                throw new Exceptions\InvalidFileException("$filename doesn't exist");
            }
            throw new Exceptions\InvalidFileException("$filename is not readable");
        }
        if ((false !== strpos('w', $this->_modifier) || false !== strpos('a', $this->_modifier)) && !$this->writable($filename)) {
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
     * Open is a method that sets a local file pointer
     *
     * @return null
    **/
    public function open()
    {
        if ($this->_fp === null) {
            $this->_fp = fopen($this->_filename, $this->_modifier);

        } else {
            throw new Exceptions\FilePointerExistsException(
                'A filepointer exists on this object, use CSVFile::close to'
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
                'The filepointer is null on this object, use CSVFile::open'
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
                'The filepointer is null on this object, use CSVFile::open'
                .' to open a new filepointer'
            );
        }
    }
}
