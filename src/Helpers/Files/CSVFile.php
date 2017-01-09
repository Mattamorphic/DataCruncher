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

use Symfony\Component\Stopwatch\Stopwatch;

use mfmbarber\DataCruncher\Exceptions\InvalidFileException;

class CSVFile extends DataFile implements DataInterface
{
    // The amount of the file to load into memory in one chunk
    const CHUNK_SIZE = 100000;
    const SORT_CHUNK_SIZE = 10000000;

    // The amount of lines to store  in memory before writing to the output
    const WRITE_BUFFER_LIMIT = 500;

    // file meta info
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
            $row = $this->getNextDataRow()->current();
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
            $this->getNextDataRow()->current();
            $this->close();
        }
        return $this->_headers;
    }
    /**
     * Calls the _getcsv method to get the next line, if it
     * exists. The process then creates an a set of key value pairs that
     * depict that line (headers and values)
     * GENERATOR
     *
     * @param bool  $peek   Peek will not remove the returned line
     *
     * @return array
    **/
    public function getNextDataRow(bool $peek = false)
    {
        if (false === stripos($this->_modifier, 'r')) {
            throw new Exceptions\InvalidFileException("File is not set to read mode");
        }
        if ($this->_headers === []) {
            $this->_headers = $this->_getCsv(self::CHUNK_SIZE);
        }
        while ([] !== ($line = $this->_getCsv(self::CHUNK_SIZE, $peek))) {
            yield @array_combine($this->_headers, $line);
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

    /**
     * Override the open method, to clear the headers
    **/
    public function open()
    {
      $this->_headers = [];
      parent::open();
    }

    /**
     * Override the reset method, to clear the headers
    **/

    public function reset()
    {
        $this->_headers = [];
        parent::reset();
    }

    /**
     * Override the parent DataFile close method, because we're using chunked writing - we don't
     * necessarily know if the buffer is empty, so let's just write it to the output stream
     *
     * @return null
    **/
    public function close()
    {
        $this->flushBuffer();
        parent::close();
    }

    /**
     * Flush the write buffer (generally called before closing the file)
     *
     * @return null
    **/
    public function flushBuffer()
    {
        if ($this->_fp !== null) {
            if (isset($this->_write_buffer)) {
                $meta = stream_get_meta_data($this->_fp);
                // todo : check if a or w in mode
                if (Validation::multiStripos($meta['mode'], ['w', '+', 'a'], true)) {
                    fwrite($this->_fp, Validation::arrayToCSV($this->_write_buffer, $this->_delimiter, $this->_encloser));
                    $this->_write_buffer = [];
                }
            }
            if (isset($this->_chunk)) {
                $this->_chunk = [];
                $this->_buffer = '';
            }
        }
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
    public function sort(string $key) : array
    {
        // Timing
        $timer = new Stopwatch();
        $timer->start('sort');
        $this->open();
        // dump the headers and load chunk 1
        $a = $this->getNextDataRow(true)->current();
        // Sort this csv into a set of sorted files
        $csvs = $this->_createSortedChunks($key);
        $this->close();
        // setup an output file
        $output = new CSVFile();
        $output->setSource($this->getSourceName(), ['modifier' => 'wb']);
        $output->open();
        // create an array holding the current comparisons
        $cmps = array_fill(0, count($csvs), false);
        $lines = 0;
        // while there are still more than 1 sorted files
        while (count($csvs) > 1) {
            // references to finished files will be stored here
            $remove = [];
            // store a current reference to the file pointer location for each open file
            // create a base low state
            $low = ['index' => null, 'data' => []];
            foreach ($csvs as $i => $csv) {
                if (!$cmps[$i]) {
                    if (!($cmps[$i] = $csv->getNextDataRow(true)->current())) {
                        $remove[] = $i;
                        continue;
                    }
                }
                if (!$low['index'] || $cmps[$i][$key] <= $low['data'][$key]) {
                    $low = ['index' => $i, 'data' => $cmps[$i]];
                }
            }
            // close any complete csvs - as we don't need them
            foreach ($remove as $i) {
                $csvs[$i]->close();
                unlink($csvs[$i]->getSourceName());
                unset($csvs[$i]);
            }
            // update any moved pointers (for low)
            if (isset($csvs[$low['index']]))
            {
                $csvs[$low['index']]->getNextDataRow()->current();
            }
            // write the output row as the low
            if ($low['data'] !== []) {
                $output->writeDataRow($low['data'])
            };
            ++$lines;
            // remove the line from the comparisons array (so a new one is fetched)
            $cmps[$low['index']] = false;
        }
        // write any remaining lines
        $csv = array_pop($csvs);
        foreach ($csv->getNextDataRow() as $line) {
            $output->writeDataRow($line);
            ++$lines;
        }
        $csv->close();
        unlink($csv->getSourceName());
        $output->close();
        $event = $timer->stop('sort');
        return [
            'duration' => $event->getDuration(),
            'max_memory' => $event->getMemory(),
            'lines' => $lines
        ];
     }

    /**
     * Return the current chunk
     *
     * @return array
    **/
    public function getChunk() : array
    {
        return $this->_chunk;
    }

    /**
     * Return the current buffer
     *
     * @return string
    **/
    public function getBuffer() : string
    {
        return $this->_buffer;
    }

    /**
     * Chunk the original CSV into separate files and store reference to these -
     * instantiated as CSVFiles
     *
     * @param string $key   The key to sort on
     *
     * @return array
     **/

     private function _createSortedChunks(string $key) : array
     {
         $csvs = [];
         // change the key into an integer
         $key = array_search($key, $this->_headers);
         // while we still have data in the file
         do
         {
             usort(
                 $this->_chunk,
                 function($a, $b) use ($key) {
                     $a = str_getcsv($a);
                     $b = str_getcsv($b);
                     return (
                         (!isset($a[$key]) || !isset($b[$key])) ?
                         -1 : $a[$key] <=> $b[$key]
                     );

                 }
             );
             // add the CSV to our list of merge parts
             do {
                 $filename = 'tmp/'.$this->_generateRandomString();
             } while (file_exists($filename));
             // Create a new CSVFile with the filename
             $csv = new CSVFile();
             $csv->setSource($filename, ['modifier' => 'wb+']);
             $csv->open();
             // write the headers
             fwrite($csv->_fp, implode(",", $this->_headers) . "\n");
             // write the lines to the file
             fwrite($csv->_fp, implode(
                 "\n",
                 array_filter(
                     $this->_chunk,
                     function ($line) { 
                         return (bool) strlen($line);
                     }
                 )
             ));
             // close the CSVFile
             $csv->close();
             // Create a new CSVFile from the same file in read mode
             $csv =  new CSVFile();
             $csv->setSource($filename, ['modifier' => 'r']);
             $csv->open();
             // set the headers
             $csv->getNextDataRow(true)->current(); // dump the headers;
             $csvs[] = $csv;
              // get 8 meg loaded into a chunk
         } while ($this->_getNextChunk(self::SORT_CHUNK_SIZE));
         return $csvs;
     }

    /**
     * _getcsv is a private method that uses the file pointer to get the next line
     * It uses a chunked method where it loads CHUNK_SIZE of string data into memory
     * This reduces the latency of reading from the file every time we want a line
     *
     * @param int   $size   The size of the chunk in bytes
     * @param bool  $peek   Toggle shifting the line off the top of the stack, or simply returning it
     *
     * @return array / bool
    **/
    private function _getCsv(int $size, bool $peek = false) : array
    {
        if (empty($this->_chunk)) {
            if (feof($this->_fp)) {
                return [];
            }
            $this->_getNextChunk($size);
        }
        //$row = fgetcsv($this->_fp, 1000, $this->_delimiter, $this->_encloser);
        $line = (!$peek) ? array_shift($this->_chunk) : $this->_chunk[0];
        return "" === ($line)  ? [] : array_map('trim', str_getcsv($line));
    }

    /**
     * Gets the next chunk of the byte size allocated in the argument
     * @param int   $size   The size of the chunk to grab in bytes
     *
     * @return boolean
    **/
    private function _getNextChunk(int $size) : bool
    {
        if (feof($this->_fp)) {
            if (!$this->_buffer) {
                return false;
            }
            $this->_chunk = $this->_buffer;
        } else {
            // if we're empty get the next chunk using the previous buffer
            $this->_chunk = $this->_buffer . fread($this->_fp, $size);
            $this->_buffer = null;
        }
        // explode the chunk into lines
        $this->_chunk = preg_split("/\\r\\n|\\r|\\n/", $this->_chunk);
        // if this isn't the end of the file buffer out the last line
        if (!feof($this->_fp)) {
            // buffer out the last line
            $this->_buffer = array_pop($this->_chunk);
        }
        return true;
    }

    /**
     * _putcvsv is a private method that writes a set of files to the output stream a
     * chunk at a time, again this reduces latency when writing to the file. A validation
     * method returns an array of csv lines as a single csv string.
    **/
    private function _putCSV(array $row) : bool
    {
        $result = true;
        $this->_write_buffer[] = $row;
        ++$this->_write_buffer_counter;
        if ($this->_write_buffer_counter >= self::WRITE_BUFFER_LIMIT) {
            $result = (bool) fwrite($this->_fp, Validation::arrayToCSV($this->_write_buffer, $this->_delimiter, $this->_encloser));
            $this->_write_buffer = [];
            $this->_write_buffer_counter = 0;
        }
        return $result;
    }

    /**
     * Generates a random string of characters of a given length
     *
     * @param int   $length     The length of the random string to create
     *
     * @return string
    **/
    private function _generateRandomString(int $length = 10) : string
    {
        return substr(
            str_shuffle(
                str_repeat(
                    $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
                    (int) ceil($length/strlen($x)) // type cast incase we have a float
                )
            ),
            1,
            $length
        );
    }

}
