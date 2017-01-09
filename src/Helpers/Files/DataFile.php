<?php
/**
 * Abstract Data File Handler (shared methods)
 *
 * @package DataCruncher
 * @subpackage Helpers
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
declare(strict_types=1);
namespace mfmbarber\DataCruncher\Helpers\Files;

use mfmbarber\DataCruncher\Exceptions;

abstract class DataFile
{

    public $_fp = null;

    protected $_modifier = 'r';
    protected $_filename = '';
    /**
     * Sets the source  file of the Manipulator object, if valid sets attributes
     *
     * @param string $filename   The name of the file to set as the source
     * @param array  $properties Data properties, modifier, encloser, delimiter
     *
     * @return null
    **/
    public function setSource(string $filename, array $properties = [])
    {
        if (isset($properties['modifier'])) {
            $this->_modifier = strtolower($properties['modifier']);
        }
        if (false !== strpos('r', $this->_modifier) && !$this->readable($filename)) {
            if (!$this->fileExists($filename)) {
                throw new Exceptions\InvalidFileException("File doesn't exist");
            }
            throw new Exceptions\InvalidFileException("File is not readable");
        }
        if ((false !== strpos('w', $this->_modifier) || false !== strpos('a', $this->_modifier))) {
            if (!file_exists($filename)) {
                touch($filename);
            }
            if (!$this->writable($filename)) {
                throw new Exceptions\InvalidFileException("File is not writable");
            }
        }
        $this->_filename = $filename;
        if (isset($properties['delimiter'])) {
            $this->_delimiter = $properties['delimiter'];
        }
        if (isset($properties['encloser'])) {
            $this->_encloser = $properties['encloser'];
        }
    }

    /**
     * Update the modifier
     *
     * @param string $modifier  The modifier to apply
    **/
    public function setModifier(string $modifier)
    {
        $this->setSource($this->_filename, ['modifer' => $modifier]);
    }

    /**
     * A local copy of fileExists to allow us to mock this function
    **/
    public function fileExists(string $filename)
    {
        return (bool) file_exists($filename);
    }

    /**
     * We abstract this method as we might change our check for writable in future
     * @param string $filename  The name of the file to check is writable
     *
     * @return bool
    **/
    public function writable(string $filename)
    {
        return (bool) is_writable($filename);
    }

    /**
     * We abstract this method as we might change our check for readable in future
     * @param string $filename  The name of the file to check is writable
     *
     * @return bool
    **/
    public function readable(string $filename)
    {
        return (bool) is_readable($filename);
    }

    /**
     * Returns the type of the source
     * @return string
    **/
    public function getType() : string
    {
        return 'file';
    }

    /**
     * Returns the current filename tied to this object
     *
     * @return string
    **/
    public function getSourceName() : string
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
            return true;
        } else {
            throw new Exceptions\FilePointerExistsException(
                'A filepointer exists on this object, use class::close to'
                .' close the current pointer'
            );
        }
    }
    /**
     * Reset the file pointer to the start of the file
     *
     * @return void
     */
    public function reset()
    {
        if ($this->_fp !== null) {
            rewind($this->_fp);
        } else {
            throw new Exceptions\FilePointerInvalidException(
                'The filepointer is null on this object, use class::open'
                .' to open a new filepointer'
            );
        }
    }
    /**
     * Close closes the file pointer attribute
     *
     * @return void
    **/
    public function close()
    {
        if ($this->_fp !== null) {
            fclose($this->_fp);
            $this->_fp = null;
        } else {
            throw new Exceptions\FilePointerInvalidException(
                'The filepointer is null on this object, use class::open'
                .' to open a new filepointer'
            );
        }
    }
}
