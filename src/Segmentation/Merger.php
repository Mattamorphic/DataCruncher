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
     */
    public function fromSource(DataInterface $source)
    {
        $this->_sources[] = $source;
        return $this;
    }
    /**
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
     */
    public function deduplicate()
    {
        // TODO : Implement this.
        return $this;
    }
    /**
     */
    public function execute(DataInterface $outfile = null, $node_name = '', $start_element = null)
    {
        if (count($this->_sources) === 0) {
            throw new InvalidArgumentException("Set some sources to merge using class::source");
        }
        // TODO : Check to see if fields are in the source and merge
        array_walk(
            $this->_sources,
            function ($source) use ($node_name, $start_element) {
                Validation::openDataFile($source, $node_name, $start_element);
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
