<?php
/**
 * Statistics Processor
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


class Statistics extends Runner
{
    private $_type;
    private $_round;
    private $_rules = [];

    public function __construct()
    {
        $this->_type = 'TOTAL';
        $this->_option = null;
    }
    /**
     * Sets the type of response to percentage rather than totals
     *
     * @return Statistics
    **/
    public function percentages(int $round = null) : Statistics
    {
        $this->_type = 'PERCENT';
        $this->_round = $round;
        return $this;
    }

    /**
     * Adds a rule to the rule stack
     *
     * @return Statistics
    **/
    public function addRule(Rule $rule) : Statistics
    {
        $this->_rules[] = $rule->get();
        return $this;
    }

    /**
     * Execute the statistics calculation given the parameters are set
     * returns an associative array of key value results
     *
     * @return array
    **/
    public function execute() : array
    {
        // TODO :: Validation of object vars.
        $idx = 0;
        $keys = [];
        // We need to figure out a key for each rule
        array_walk(
            $this->_rules,
            function (&$rule) use (&$idx, &$keys) {
                $rule->label = $rule->label ?? $idx++;
                $keys[] = $rule->label;
            }
        );
        // then we need to create a results array, where each element is
        // based on a rule
        $results = array_fill_keys($keys, []);
        Validation::openDataFile($this->_source);
        if ($this->_timer) $this->_timer->start('execute');
        foreach ($this->_source->getNextDataRow() as $rowTotal => $row) {
            foreach ($this->_rules as $key => $rule) {
                $this->processRow($results[$rule->label], $row, $rule);
            }
        }
        ++$rowTotal;
        // For percentages calculate the percentage based on the total rows
        if ($this->_type === 'PERCENT') $this->convertToPercent($results, $rowTotal);
        $this->closeOut($results);
        return $results;
    }

    /**
     * Process the rule against the row
     * @param array     &$result    The result of applying the rule
     * @param array     $row        The row to test
     * @param object     $rule       The rule
     *
     * @return null
    **/
    public function processRow(array &$result, array $row, \stdClass $rule)
    {
        // invoke the closure assigned to the attribute (our statistics func)
        $func = $rule->func;
        $key = $func($row[$rule->field], $rule->option);
        if (false !== $key) {
            if (!array_key_exists($key, $result)) {
                $result[$key] = 0;
            }
            ++$result[$key];
        }
    }

    /**
     * Converts the results from a standard numerical representation
     * To a percentage - with optional rounding based on the methods
     * called
     *
     * @param array     &$results   The results to convert
     * @param int       $total      The total amount of records
    **/
    private function convertToPercent(array &$results, int $total)
    {
        foreach ($results as &$result) {
            foreach ($result as $key => $value) {
                $result[$key] = (100 / $total) * $value;
                if ($this->_round) {
                    $result[$key] = round($result[$key], $this->_round);
                }
            }
        }
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
