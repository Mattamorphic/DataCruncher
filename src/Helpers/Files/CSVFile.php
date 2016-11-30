<?php
/**
 * CSVFile Handler
 *
 * @package DataCruncher
 * @subpackage Helpers
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
declare(strict_types=1);
namespace mfmbarber\DataCruncher\Helpers\Files;

use mfmbarber\DataCruncher\Exceptions;
use mfmbarber\DataCruncher\Config\Validation;
use mfmbarber\DataCruncher\Helpers\Interfaces\DataInterface;

use mfmbarber\DataCruncher\Exceptions\InvalidFileException;

class CSVFile extends DataFile implements DataInterface
{
    // The amount of the file to load into memory in one chunk
    const CHUNK_SIZE = 4096;
    // The amount of lines to store  in memory before writing to the output
    const WRITE_BUFFER_LIMIT = 50;

    private $_headers = [];
    private $_delimiter = ',';
    private $_encloser = "\"";

    // current chunk and the buffer
    private $_chunk = [];
    private $_buffer = '';

    // current write buffer and the length of the write buffer
    private $_write_buffer = [];
    private $_write_buffer_counter = 0;

    public function setSource(string $filename, array $properties = [])
    {
        parent::setSource($filename, $properties);
        if (stripos($this->_modifier, 'r') !== false) {
            $this->open();
            $row = $this->getNextDataRow();
            $this->close();
            if (!$row) {
                throw new Exceptions\InvalidFileException("The file provided is not in the correct format");
            }
        }
    }
    /**
     * Opens a file at the beginning, reads a line and closes the file
     * Returns the configured fields
     *
     * @return array
    **/
    public function getHeaders($force = true) : array
    {
        if ($force || $this->_headers === []) {
            $this->open();
            $this->getNextDataRow();
            $this->close();
        }
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
        if (false === stripos($this->_modifier, 'r')) {
            throw new Exceptions\InvalidFileException("File is not set to read mode");
        }
        if ([] !== ($line = $this->_getCsv())) {
            // trim all the values in the array of values
            $line = array_map('trim', $line);
            // if we are yet to get the headers, then this is the header line
            // so set it
            if ($this->_headers === []) {
                $this->_headers = $line;
                return $this->getNextDataRow();
            } else {
                return @array_combine($this->_headers, $line);
            }
        } else {
            return [];
        }
    }
    /**
     * Calls the _putcsv method to push a row to the output stream (if it exists)
     * This method returns null on success, and throws an error if there are any errors
     *
     * @return null
    **/
    public function writeDataRow(array $row)
    {
        if ($this->_fp !== null) {
            if ($this->_headers === []) {
                $this->_headers = array_keys($row);
                if (false === fputcsv($this->_fp, $this->_headers, $this->_delimiter, $this->_encloser)) {
                    throw new \RuntimeException("Couldn't write to {$this->getSourceName()}");
                }
            }
            if (false === $this->_putCSV($row)) {
                throw new \RuntimeException("Couldn't write to {$this->getSourceName()}");
            }
        } else {
            throw new Exceptions\FilePointerInvalidException(
                'The filepointer is null on this object, use class::open'
                .' to open a new filepointer'
            );
        }
    }
    public function open()
    {
      $this->_headers = [];
      parent::open();
    }

    public function reset()
    {
        $this->_headers = [];
        parent::reset();
    }

    /**
     * Override the parent DataFile close method, because we're using chunked writing - we don't
     * necessarily know if the buffer is empty, so let's just write it to the output stream
     *
    **/
    public function close()
    {
        if ($this->_fp !== null) {
            if (isset($this->_write_buffer)) {
                $meta = stream_get_meta_data($this->_fp);
                // todo : check if a or w in mode
                if ($meta['mode'] === 'w') {
                    fwrite($this->_fp, Validation::arrayToCSV($this->_write_buffer, $this->_delimiter, $this->_encloser));
                    $this->_write_buffer = [];
                }
            }
            if (isset($this->_chunk)) {
                $this->_chunk = [];
                $this->_buffer = '';
            }
        }
        parent::close();
    }

    /**
      * Sorts a data file using the unix sort command
      * unfortunately this is not compatible with Windows systems
      * just *nix
      *
      * @param $key   The header to sort by
      *
      *
      * Note: Currently this only supports numeric and string sorting
      * NOT dates, nor does it operate with multiple enclosed values in a field
      * As the separator is ','
      *
    **/
    public function sort($key, $isInt = false, $backup = true)
    {
        if (PHP_SHLIB_SUFFIX === 'dll') {
            throw new \DomainException("Sorting can only be carried out on a *nix system");
        }
        $headers = array_flip($this->getHeaders());
        $key = (int) $headers[trim($key)] + 1;
        $cmd = "(head -n 1 {$this->_filename} ; tail -n +2 {$this->_filename} | sort ".($isInt ? "-n" : "")." --field-separator=',' --key=$key) > {$this->_filename}.bak && \cp {$this->_filename}.bak {$this->_filename}";
        $res = shell_exec($cmd);
        if (!$backup) {
            unlink("{$this->_filename}.bak");
        }
        if ($res !== null) {
            throw new \Exception("Couldn't execute sort, error : $res");
        }
     }

    /**
     * _getcsv is a private method that uses the file pointer to get the next line
     * It uses a chunked method where it loads CHUNK_SIZE of string data into memory
     * This reduces the latency of reading from the file every time we want a line
     *
     * @return array / bool
    **/
    private function _getCsv() : array
    {
        if (empty($this->_chunk)) {
            if (feof($this->_fp)) {
                return [];
            }
            // if we're empty get the next chunk using the previous buffer
            $this->_chunk = $this->_buffer . fread($this->_fp, self::CHUNK_SIZE);
            // explode the chunk into lines
            $this->_chunk = preg_split("/\\r\\n|\\r|\\n/", $this->_chunk);
            // if this isn't the end of the file
            if (!feof($this->_fp)) {
                // buffer out the last line
                $this->_buffer = array_pop($this->_chunk);
            }
        }
        //$row = fgetcsv($this->_fp, 1000, $this->_delimiter, $this->_encloser);
        return ("" === ($line = array_shift($this->_chunk)))  ? [] : str_getcsv($line);
    }

    /**
     * _putcvsv is a private method that writes a set of files to the output stream a
     * chunk at a time, again this reduces latency when writing to the file. A validation
     * method returns an array of csv lines as a single csv string.
    **/
    private function _putCSV(array $row) : bool
    {
        $result = true;
        if ($this->_write_buffer_counter >= self::WRITE_BUFFER_LIMIT) {
            $result = (bool) fwrite($this->_fp, Validation::arrayToCSV($this->_write_buffer, $this->_delimiter, $this->_encloser));
            $this->_write_buffer = [];
            $this->_write_buffer_counter = 0;
        } else {
            $this->_write_buffer[] = $row;
            ++$this->_write_buffer_counter;
        }
        return $result;
    }
}
