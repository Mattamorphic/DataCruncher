<?php
/**
 * Find Processor
 *
 * @package DataCruncher
 * @subpackage Analysis
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
declare(strict_types=1);
namespace mfmbarber\DataCruncher\Analysis;

use mfmbarber\DataCruncher\Analysis\Config\Rule as Rule;
use mfmbarber\DataCruncher\Config\Validation as Validation;
use mfmbarber\DataCruncher\Helpers\Interfaces\DataInterface as DataInterface;
use mfmbarber\DataCruncher\Exceptions;
use mfmbarber\DataCruncher\Runner as Runner;


class Find extends Runner {

    private $min = null;
    private $max = null;
    private $deviation = null;

    public function __construct()
    {
        $deviation = new \StdClass();
        $deviation->field = null;
        $deviation->threshold = 0;
        $deviation->average = 0;
        $deviation->return = [];
        $this->deviation = $deviation;
    }

    /**
     * Sets the field to check for a minimum value.
     *
     * @param   string  $field  The field to use to check the minimum value
     *
     * @return Find
    **/
    public function min(string $field) : Find
    {
        $this->min = $field;
        return $this;
    }

    /**
     * Sets the field to check for a maximum value.
     *
     * @param   string  $field  The field to use to check the maximum value
     *
     * @return Find
    **/
    public function max(string $field) : Find
    {
        $this->max = $field;
        return $this;
    }

    /**
     * Tells the system to calculate the deviation for each row
     *
     * @param   string  $field      The field to use to check the deviation
     * @param   int     $threshold  The acceptable deviation
     *
     * @return Find
    **/
    public function deviation(string $field, int $threshold = 0, array $return = null) : Find
    {
        // We need to scan the file to get the average value - no way around this.
        $this->deviation->field = $field;
        $this->deviation->threshold = $threshold;
        $this->deviation->return = array_flip(($return) ?? $this->_source->getHeaders());
        return $this;
    }

    /**
     * Execute the find (min, max, deviations) tests
    **/
    public function execute()
    {
        Validation::openDataFile($this->_source);
        ($this->_timer) ? $this->_timer->start('execute') : null;
        $min = null;
        $max = null;
        $deviations = [];
        $results = [];
        if ($this->deviation->field) $this->getAverage($this->deviation->field);
        foreach ($this->_source->getNextDataRow() as $ln => $row)
        {
            // We don't write this to an out file as it's tiny!
            $this->checkRow($row, $min, $max);
            if ($this->deviation->field) {
                $this->setDeviation($row);
                ($this->_out) ? $this->_out->writeDataRow($row) : $deviations[] = $row;
            }
        }

        if ($min) $results['min'] = $min;
        if ($max) $results['max'] = $max;
        if ($deviations !== []) $results['deviations'] = $deviations;
        $this->closeOut($results);
        return $results;
    }

    /**
     * Caclulates the min and max
     *
     * @param array     $row    The target row
     * @param array     $min    The current min row
     * @param array     $max    The current max row
    **/
    private function checkRow(array $row, &$min, &$max)
    {
        if ($this->min) {
            $min = ($min) ? ((float) $min[$this->min] > (float) $row[$this->min]) ? $row : $min : $row;
        }
        if ($this->max) {
            $max = ($max) ? ((float) $max[$this->max] < (float) $row[$this->max]) ? $row : $max : $row;
        }
    }

    /**
     * Updates the row with the deviation information
     *
     * @param array     $row    The row to add the deviation data to
    **/
    private function setDeviation(array &$row)
    {
        // if we have devaitions then let's add these to a mapped object
        $deviation = $row[$this->deviation->field] - $this->deviation->average;
        $row = array_intersect_key($row, $this->deviation->return);
        $row[$this->deviation->field . '_deviation'] = $deviation;
    }

    /**
     * calculate the average for a given source and store this for deviation calculations
     *
     * @param string    $field  The field to calculate the average for
    **/
    private function getAverage(string $field)
    {
        $total = 0;
        foreach ($this->_source->getNextDataRow() as $ln => $row) {
            $total += $row[$this->deviation->field];
        }
        $this->_source->reset();
        $this->deviation->average = $total / ($ln + 1); // to account for 0 index
    }

    /**
     * Close the source, and apply any timing metrics
     *
     * @param array     &$results   The results
    **/
    private function closeOut(array &$results)
    {
        //if ($output !== null) {
            // foreach ($results as $key => &$result) {
            //     foreach ($result as $key => $value) {
            //         $row = [
            //             $this->_rules[$key]['field'] => $key,
            //             $this->_type => $value
            //         ];
            //         $output->writeDataRow($result);
            //     }
            // }
            //$output->close();
            //return true;
        //}
        // if we have a single result - return that
        $this->_source->close();
        if ($this->_timer) {
            $time = $this->_timer->stop('execute');
            $results['data'] = $results;
            $results['timer'] = [
                'elapsed' => $time->getDuration(), // milliseconds
                'memory' => $time->getMemory() // bytes
            ];
        }
    }

}
