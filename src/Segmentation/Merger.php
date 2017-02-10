<?php
/**
 * Merger Processor
 *
 * @package DataCruncher
 * @subpackage Segmentation
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
declare(strict_types=1);
namespace mfmbarber\DataCruncher\Segmentation;

use mfmbarber\DataCruncher\Config\Validation;
use mfmbarber\DataCruncher\Helpers\Interfaces\DataInterface;
use mfmbarber\DataCruncher\Exceptions;
use mfmbarber\DataCruncher\Runner as Runner;

class Merger extends Runner
{
    private $sources = [];

    /**
     * Set a data source to Merge, and add this to an array of sources
     *
     * @param DataInterface $source The source to add
     *
     * @return this
     */
    public function from(DataInterface $source) : Merger
    {
        $this->sources[] = $source;
        return $this;
    }

    /**
     * Set the field to use as the merge index
     *
     * @param string  $field The field to merge on, must exist across all
     *
     * @throws InvalidArgumentException when parameter is not a String.
     *
     * @return this
     */
    public function using(string $field) : Merger
    {
        $this->field =  $field;
        return $this;
    }

    /**
     * Runs the merging of the data sets
     *
     * @return mixed
     */
    public function execute() : array {
        // if there are no soruces then throw an exception
        if (!count($this->sources)) {
            throw new \InvalidArgumentException("Set some sources to merge using class::source");
        }
        array_walk(
            $this->sources,
            /**
             * Walk over each source, open this, and check that the field exists in the file
             * @param DataInterface &$source     The source file to open
             *
             * @throws InvalidArgumentException
             * @return void
             */
            function ($source) {
                Validation::openDataFile($source);
            }
        );
        if ($this->timer) $this->timer->start('execute');
        $result = [];
        // While there are sources to merge
        do {
            // get a source
            $analyse = array_shift($this->sources);
            // if the field is valid, process it
            foreach ($analyse->getNextDataRow() as $rowNumber => $row) {
                if ($rowNumber === 0 && !isset($row[$this->field])) {
                    throw new \InvalidArgumentException("$this->field not found in {$analyse->getSourceName()}");
                }
                $this->processRow($result, $row);
                // while we have rows to process against
            }
            // reset the analyses object
            $analyse->reset();
        } while (count($this->sources));
        // Foreach source, close it
        foreach ($this->sources as $source) {
            $source->close();
        }
        return $result;
    }

    /**
     * Processes a row against all lines in a source file
     *
     * @param array     &$result    The result to process
     * @param array     $row        The current row
    **/
    private function processRow(array &$result, array $row) : void
    {
        // Foreach of the remaining sources
        foreach ($this->sources as $source) {
            // While we have lines to merge
            foreach ($source->getNextDataRow() as $merge_row) {
                // if they are equal
                if ($row[$this->field] === $merge_row[$this->field]) {
                    // do your thing
                    $result[] = $row + $merge_row;
                }
            }
            $source->reset();
        }
    }

    /**
     * Close the source, and apply any timing metrics
     *
     * @param array     &$results   The results
    **/
    private function closeOut(array &$results) : void
    {
        //if ($output !== null) {
            // foreach ($results as $key => &$result) {
            //     foreach ($result as $key => $value) {
            //         $row = [
            //             $this->rules[$key]['field'] => $key,
            //             $this->type => $value
            //         ];
            //         $output->writeDataRow($result);
            //     }
            // }
            //$output->close();
            //return true;
        //}
        // if we have a single result - return that

        array_walk(function (&$source) { $source->close(); }, $this->sources);
        if ($this->timer) {
            $time = $this->timer->stop('execute');
            $results['data'] = $results;
            $results['timer'] = [
                'elapsed' => $time->getDuration(), // milliseconds
                'memory' => $time->getMemory() // bytes
            ];
        }
    }
}
