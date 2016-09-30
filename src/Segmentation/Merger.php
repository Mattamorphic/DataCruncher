<?php
/**
 * Merger Processor
 *
 * @package Data_Cruncher
 * @subpackage Segmentation
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
declare(strict_types=1);
namespace mfmbarber\Data_Cruncher\Segmentation;

use mfmbarber\Data_Cruncher\Config\Validation;
use mfmbarber\Data_Cruncher\Helpers\Interfaces\DataInterface;
use mfmbarber\Data_Cruncher\Exceptions;

class Merger
{
    private $_sources = [];

    /**
     * Set a data source to Merge, and add this to an array of sources
     *
     * @param DataInterface $source The source to add
     *
     * @return this
     */
    public function fromSource(DataInterface $source) : Merger
    {
        $this->_sources[] = $source;
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
    public function on(string $field) : Merger
    {
        $this->_field =  $field;
        return $this;
    }
    /**
     * Runs the merging of the data sets
     *
     * @param DataInterface $outfile        A file to write the output to
     * @param string        $node_name      The name of the node for each 'row' if using xml
     * @param string        $start_element  The parent node for the nodes we want to parse if using xml
     *
     * @return mixed
     */
    public function execute(DataInterface $outfile = null) : array {
        // if there are no soruces then throw an exception
        if (!count($this->_sources)) {
            throw new \InvalidArgumentException("Set some sources to merge using class::source");
        }
        array_walk(
            $this->_sources,
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
        if ($outfile !== null) {
            Validation::openDataFile($outfile, true);
        }
        $result = [];
        // While there are sources to merge
        do {
            // get a source
            $analyse = array_shift($this->_sources);
            // get the first row and check there's a matching field
            $row = $analyse->getNextDataRow();
            if (!isset($row[$this->_field])) {
                throw new \InvalidArgumentException("$this->_field not found in {$analyse->getSourceName()}");
            }
            // if the field is valid, process it
            do {
                $this->_processRow($result, $row);
                // while we have rows to process against
            } while ([] !== ($row = $analyse->getNextDataRow()));
            // reset the analyses object
            $analyse->reset();
        } while (count($this->_sources));
        // Foreach source, close it
        foreach ($this->_sources as $source) {
            $source->close();
        }
        return $result;
    }

    /**
     * Processes a row against all lines in a source file
    **/
    private function _processRow(array &$result, array $row)
    {
        // Foreach of the remaining sources
        foreach ($this->_sources as $source) {
            // While we have lines to merge 
            while ([] !== ($merge_row = $source->getNextDataRow())) {
                // if they are equal
                if ($row[$this->_field] === $merge_row[$this->_field]) {
                    // do your thing
                    $result[] = array_merge($row, $merge_row);
                }
            }
            $source->reset();
        }
    }
}
