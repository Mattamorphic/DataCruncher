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

use Symfony\Component\Stopwatch\Stopwatch;
use mfmbarber\DataCruncher\Analysis\Config\Rule as Rule;

use mfmbarber\DataCruncher\Config\Validation as Validation;
use mfmbarber\DataCruncher\Helpers\Interfaces\DataInterface as DataInterface;
use mfmbarber\DataCruncher\Exceptions;

class Statistics
{
    private $_source;
    private $_type;
    private $_rules = [];

    public function __construct()
    {
        $this->_type = 'TOTAL';
        $this->_option = null;
    }
    /**
     * Sets the data source for the query
     *
     * @param DataInterface $sourceFile The data source for the query
     *
     * @return Statistics
     **/
    public function fromSource(DataInterface $sourceFile) : Statistics
    {
        $this->_source = $sourceFile;
        return $this;
    }
    /**
     * Sets the type of response to percentage rather than totals
     *
     * @return Statistics
    **/
    public function percentages() : Statistics
    {
        $this->_type = 'PERCENT';
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
     * @param Helpers\DataInterface $output a location to populate with results
     *
     * @return array
    **/
    public function execute(DataInterface $output = null, bool $timer = false) : array
    {
        // TODO :: Validation of object vars.
        $stopwatch = new Stopwatch();
        $idx = 0;
        $keys = [];
        $rowTotal = 0;
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
        // if ($output !== null) {
        //     Validation::openDataFile($output);
        // }
        ($timer) ? $stopwatch->start('execute') : null;
        foreach ($this->_source->getNextDataRow() as $rowTotal => $row) {
            foreach ($this->_rules as $key => $rule) {
                $this->processRow($results[$rule->label], $row, $rule);
            }
        }
        ++$rowTotal;
        // For percentages calculate the percentage based on the total rows
        if ($this->_type == 'PERCENT') {
            foreach ($results as &$result) {
                foreach ($result as $key => $value) {
                    $result[$key] = (100 / $rowTotal) * $value;
                }
            }
        }
        $this->_source->close();
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
        if ($timer) {
            $time = $stopwatch->stop('execute');
            $results['data'] = $results;
            $results['timer'] = [
                'elapsed' => $time->getDuration(), // milliseconds
                'memory' => $time->getMemory() // bytes
            ];
        }
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
}
