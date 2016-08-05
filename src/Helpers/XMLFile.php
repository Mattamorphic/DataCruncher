<?php
/**
 * XML File
 *
 * @package Data_Cruncher
 * @subpackage Helpers
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
namespace mfmbarber\Data_Cruncher\Helpers;

use mfmbarber\Data_Cruncher\Exceptions;

class XMLFile extends DataFile implements DataInterface
{
    private $node_name;
    private $start_element;
    private $_read;
    /**
     * Returns the next row of data from a file, if there are no rows locale_accept_from_http
     * this returns false
     *
     * @return mixed
     */
    public function getNextDataRow()
    {
        $row = [];
        while ($this->_fp->name === $this->node_name) {
            $row = $this->_toArray(new \SimpleXMLElement($this->_fp->readOuterXML()));
            break;
        }
        $this->_fp->next($this->node_name);
        return $row;
    }
    /**
     * Writes a row of data to an output file.
     *
     * @param array $row    The row of data to write back to the file
     *
     * @return boolean
     */
    public function writeDataRow(array $row)
    {
        if ($this->_fp !== null) {
            if (count($row) === 0) {
                return false;
            }
            $this->_fp->startElement($this->node_name);
            foreach ($row as $key => $value) {
                $this->_fp->writeElement($key, $value);
            }
            $this->_fp->endElement();
            $this->_fp->flush();
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
     * @return void
     */
    public function open($read = true, $node_name = null, $start_element = null)
    {
        if ($this->_fp === null) {
            $this->node_name = $node_name;
            if ($read) {
                $this->_fp = new \XMLReader();
                $this->_read = $read;
                $this->_fp->open($this->_filename);
                while ($this->_fp->read() && $this->_fp->name !== $this->node_name);
            } else {
                $this->_fp = new \XMLWriter();
                $this->_read = false;
                $this->_fp->openURI($this->_filename);
                $this->_fp->startDocument('1.0');
                if (null !== $start_element) {
                    $this->start_element = $start_element;
                    $this->_fp->startElement($start_element);
                }
            }
        } else {
            throw new Exceptions\FilePointerExistsException(
                'A filepointer exists on this object, use class::close to'
                .' close the current pointer '
            );
        }
    }
    /**
     * Close closes the file pointer attribute
     *
     * @return void
     */
    public function close()
    {
        if ($this->_fp !== null) {
            if (get_class($this->_fp) === 'XMLReader') {

            } else {
                $this->_fp->endDocument();
                $this->_fp->flush();
            }
            $this->_fp = null;
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
    public function reset()
    {
        if ($this->_fp !== null) {
            // we have to close and reopen
            $this->close();
            $this->open($this->_read, $this->node_name, $this->start_element);
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
    private function _toArray($xml, $row = [])
    {
        // if the data is an object, we get this as an array, else we return the xml
        $data = (is_object($xml)) ? get_object_vars($xml) : $xml;
        // if the data is an array...
        if (is_array($data)) {
            // we loop across building our equivelent with recursive calls
            foreach ($data as $key => $value) {
                $res = [];
                $res = $this->_toArray($value, $res);
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
