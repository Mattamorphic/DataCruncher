<?php
/**
 * XML File
 *
 * @package DataCruncher
 * @subpackage Helpers
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
declare(strict_types=1);
namespace mfmbarber\DataCruncher\Helpers\Files;

use mfmbarber\DataCruncher\Exceptions;
use mfmbarber\DataCruncher\Helpers\Interfaces\DataInterface;

class XMLFile extends DataFile implements DataInterface
{
    private $node_name;
    private $start_element;
    private $read;
    private $fields = [];

    public function __construct(string $node_name, string $start_element)
    {
        $this->node_name = $node_name;
        $this->start_element = $start_element;
    }

    /**
     * Opens a file at the beginning, reads a line and closes the file
     * Returns the configured fields
     *
     * @param string $node_name
     * @param string $start_element
     *
     * @return array
    **/
    public function getHeaders($force = true) : array
    {
        if ($force || $this->headers === []) {
            $this->open(true, $this->node_name, $this->start_element);
            $this->getNextDataRow()->current();
            $this->close();
        }
        return $this->fields;
    }

    /**
     * Returns the next row of data from a file, if there are no rows locale_accept_from_http
     * this returns false
     *
     * @return Generator
     */
    public function getNextDataRow() : \Generator
    {
        $row = [];
        while ($this->fp->name === $this->node_name) {
            $row = $this->toArray(new \SimpleXMLElement($this->fp->readOuterXML()));
            $this->fields  = ($this->fields === []) ? array_keys($row) : $this->fields;
            $this->fp->next($this->node_name);
            yield $row;
        }

    }
    /**
     * Writes a row of data to an output file.
     *
     * @param array $row    The row of data to write back to the file
     *
     * @return boolean
     */
    public function writeDataRow(array $row) : bool
    {
        if ($this->fp !== null) {
            if (count($row) === 0) {
                return false;
            }
            $this->fp->startElement($this->node_name);
            foreach ($row as $key => $value) {
                $this->fp->writeElement($key, $value);
            }
            $this->fp->endElement();
            $this->fp->flush();
            if ($this->fields === []) {
                $this->fields = array_keys($row);
            }
            return true;
        } else {
            throw new Exceptions\FilePointerInvalidException(
                'The filepointer is null on this object, use class::open'
                .' to open a new filepointer'
            );
        }
    }
    /**
     * open the file and set the file pointer
     *
     * @param bool  $read   Should we open this in READ mode?
     *
     * @return void
     */
    public function open(bool $read = true) : void
    {
        if ($this->fp === null) {
            if ($read) {
                $this->fp = new \XMLReader();
                $this->read = $read;
                $this->fp->open($this->filename);
                while ($this->fp->read() && $this->fp->name !== $this->node_name);
            } else {
                $this->fp = new \XMLWriter();
                $this->read = false;
                $this->fp->openURI($this->filename);
                $this->fp->startDocument('1.0');
                if (null !== $this->start_element) {
                    $this->fp->startElement($this->start_element);
                }
            }
        } else {
            throw new Exceptions\FilePointerExistsException(
                'A filepointer exists on this object, use class::close to'
                .' close the current pointer'
            );
        }
    }
    /**
     * Close closes the file pointer attribute
     *
     * @return void
     */
    public function close() : void
    {
        if ($this->fp !== null) {
            if (get_class($this->fp) === 'XMLReader') {

            } else {
                $this->fp->endDocument();
                $this->fp->flush();
            }
            $this->fp = null;
        } else {
            throw new Exceptions\FilePointerInvalidException(
                'The filepointer is null on this object, use class::open'
                .' to open a new filepointer'
            );
        }
    }
    /**
     * Reset the file pointer to the start of the file
     *
     * @return void
     */
    public function reset() : void
    {
        if ($this->fp !== null) {
            // we have to close and reopen
            $this->close();
            $this->open($this->read, $this->node_name, $this->start_element);
        } else {
            throw new Exceptions\FilePointerInvalidException(
                'The filepointer is null on this object, use class::open'
                .' to open a new filepointer'
            );
        }
    }

    /**
     * Converts simpleXMLObject to array
     *
     * @param simpleXMLObject   $xml The current element as a simple xml object
     * @param array             $row The current row represented as an array
     *
     * @return array
     */
    private function toArray($xml, array $row = [])
    {
        // if the data is an object, we get this as an array, else we return the xml
        $data = (is_object($xml)) ? get_object_vars($xml) : $xml;
        // if the data is an array...
        if (is_array($data)) {
            // we loop across building our equivelent with recursive calls
            foreach ($data as $key => $value) {
                $res = [];
                $res = $this->toArray($value, $res);
                if (($key == '@attribute') && ($key)) {
                    $row = $res;
                } else {
                    $row[$key] = trim($res);
                }
            }
        } else {
            $row = $data;
        }
        return $row;
    }
}
