<?php
/**
 * Split Processor
 *
 * @package DataCruncher
 * @subpackage Segmentation
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
namespace mfmbarber\DataCruncher\Segmentation;

use mfmbarber\DataCruncher\Exceptions as Exceptions;
use mfmbarber\DataCruncher\Config\Validation as Validation;
use mfmbarber\DataCruncher\Helpers\Interfaces\DataInterface as DataInterface;

class Split
{
    private $_source;

    /**
     * Sets the data source for the split
     *
     * @param DataInterface $source The data source for the query
     *
     * @return Split
    **/
    public function fromSource(DataInterface $source)
    {
        $this->_source= $source;
        return $this;
    }
    /**
     * Split the data horizontally into chunks of X size
     *
     * @param integer $size The size of each chunk
     *
     * @return Split
     **/
    public function horizontal($size)
    {
        $this->_direction = 'HORIZONTAL';
        if (!is_int($size)) {
            throw new \InvalidArgumentException("Size expected to be an integer");
        }
        $this->_size = (int) $size;
        return $this;
    }

    /**
     * Split the data vertically
     *
     * @param array $groupings The fields for each of the groups
     *
     * @return Split
     **/
    public function vertical($groupings = [])
    {
        $this->_direction = 'VERTICAL';
        // Groupings provided as [['name', 'colour'], ['name', 'age']] where each element
        // defines a groups headers
        $this->_groups = $this->_setGroupings($groupings);
        return $this;
    }

    /**
     * Execute the split, returning an array of arrays, where each sub array
     * is a row of headers and values
     *
     * @param array(Helpers\DataInterface) $outfile a location to populate with results
     *
     * @return array
    **/
    public function execute(array $outfiles = [], $node_name = '', $start_element = '')
    {
        $result = [];
        $set = [];
        $ticker = false; // horizontal ticker

        Validation::openDataFile($this->_source, $node_name, $start_element);
        if (($writeOutFiles = $this->_openOutFiles($outfiles, $node_name, $start_element, true))) {
            $result = array_fill(0, count($outfiles), 0);
        }
        if ($this->_direction === 'VERTICAL') {
            $result = array_fill(0, count($this->_groups)-1, []);
        }
        foreach ($this->_source->getNextDataRow() as $row) {
            switch ($this->_direction) {
                // TODO move processing of out files into functions that handle this
                case 'HORIZONTAL':
                    // push the row on to our array of lines
                    $set[] = $row;
                    // if we're at the chunk size then...
                    if (count($set) === $this->_size) {
                        // Decide on output mode
                        if ($writeOutFiles) {
                            foreach ($set as $row) {
                                $outfiles[(int) $ticker]->writeDataRow($row);
                            }
                            $result[(int) $ticker] += count($set);
                            $ticker = !$ticker;
                        } else {
                            $result[] = $set;
                        }
                        $set = [];
                    }
                    break;
                case 'VERTICAL':
                    foreach ($this->_groups as $idx => $group) {
                        $out_row = array_intersect_key($row, $group);
                        ($writeOutFiles) ? $outfiles[$idx]->writeDataRow($out_row) : $result[$idx][] = $out_row;
                    }
                    break;
            }
        }
        $this->_source->close();
        if ($writeOutFiles) {
            foreach ($outfiles as &$outfile) {
                $outfile->close();
            }
        }
        return $result;
    }
    /**
     * Opens an array of data sources
     * @param array     $outfiles       to open (by reference)
     * @param string    $node_name      are the names of the nodes if the file being opened is xml
     * @param string    $start_elemnent is the parent element of the data nodes if the file is xml
     *
     * @throws InvalidArgumentException
     * @return Boolean
    **/
    private function _openOutFiles(&$outfiles, $node_name = '', $start_element = '')
    {
        if ($outfiles == []) {
            return false;
        }
        $count = count($outfiles);
        $error = false;
        if ($count <= 1) {
            $error = true;
        }
        if ($this->_direction === 'HORIZONTAL' && $count !== $this->_size) {
            $error = true;
        }
        if ($this->_direction === 'VERTICAL' && $count != count($this->_groups)) {
            $error = true;
        }
        if ($error) {
            throw new \InvalidArgumentException(
                "Ensure 2 outputs are provided for vertical split, and x "
                ."for horizontal split"
            );
        }
        foreach ($outfiles as &$outfile) {
            Validation::openDataFile($outfile, $node_name, $start_element);
        }
        return true;
    }
    /**
     * Set groupings creates an array of arrays, where each array represents a vertical group
     *
     * @param array     $groupings  Can be an array of comma seperated field names,
     *                              or of arrays where each field within represents a field name
     *
     * @return array
    **/
    private function _setGroupings($groupings)
    {
        $groups = [];
        foreach ($groupings as $group) {
            $group = is_array($group) ? $group : array_map('trim', explode(',', $group));
            $groups[] = array_flip($group);
        }
        return $groups;
    }
}
