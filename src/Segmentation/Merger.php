<?php
/**
 * Merger Processor
 *
 * @package Data_Cruncher
 * @subpackage Segmentation
 * @author matt barber <mfmbarber@gmail.com>
 *
 */

namespace mfmbarber\Data_Cruncher\Segmentation;

use mfmbarber\Data_Cruncher\Config\Validation as Validation;
use mfmbarber\Data_Cruncher\Helpers\DataInterface as DataInterface;
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
    public function fromSource(DataInterface $source)
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
    public function on($field)
    {
        if (!is_string($field)) {
            throw new InvalidArgumentException("Field to merge on must be a string");
        }
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
    public function execute(DataInterface $outfile = null, $node_name = '', $start_element = null)
    {
        if (count($this->_sources) === 0) {
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
            function ($source) use ($node_name, $start_element) {
                Validation::openDataFile($source, $node_name, $start_element);
                if (!in_array($this->_field, array_keys($source->getNextDataRow()))) {
                    throw \InvalidArgumentException(
                        "The merge field $this->_field field doesn't exist in "
                        . $source->getSourceName()
                    );
                } else {
                    $source->reset();
                }
            }
        );
        if ($outfile !== null) {
            Validation::openDataFile($outfile, $node_name, $start_element);
        }
        $result = [];
        while (count($this->_sources) > 0) {
            $analyse = array_shift($this->_sources);
            while ([] !== ($row = $analyse->getNextDataRow())) {
                $this->_processRow($result, $row);
            }
        }
        foreach ($this->_sources as $source) {
            $source->close();
        }
        return $result;
    }

    private function _processRow(array &$result, array $row)
    {
        foreach ($this->_sources as $source) {
            while ([] !== ($merge_row = $source->getNextDataRow())) {
                if ($row[$this->_field] === $merge_row[$this->_field]) {
                    $result[] =  array_merge($row, $merge_row);
                }
            }
            $source->reset();
        }
    }
}
