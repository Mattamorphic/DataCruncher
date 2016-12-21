<?php
/**
 * Query Processor
 *
 * @package DataCruncher
 * @subpackage Segmentation
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
declare(strict_types=1);

namespace mfmbarber\DataCruncher\Segmentation;

use mfmbarber\DataCruncher\Config\Validation as Validation;
use mfmbarber\DataCruncher\Helpers\Interfaces\DataInterface as DataInterface;
use mfmbarber\DataCruncher\Exceptions;
use mfmbarber\DataCruncher\Runner as Runner;

class Query extends Runner
{
    private $_isdb = false;
    private $_fields = [];
    private $_where = '';
    private $_condition = '';
    private $_value = '';
    private $_limit = -1;
    private $_mappings = null;

    /**
     * Sets the data source for the query
     *
     * @param DataInterface $source The data source for the query
     *
     * @return Query
    **/
    public function from(DataInterface $source)
    {
        parent::from($source);
        if (get_class($source) === 'mfmbarber\DataCruncher\Helpers\Databases\Database') {
            $this->_isdb = true;
        }
        if ($this->_fields !== []) {
            $headers = $this->_source->getHeaders();
            $fields = array_keys($this->_fields);
            if (Validation::areArraysDifferent($fields, $headers)) {
                throw new \Exception(
                    'One or more of '.implode(', ', $fields) . ' is not in ' .
                    implode(', ', $headers)
                );
            }
        }
        return $this;
    }
    /**
     * Select the fields to return from the SourceFile
     *
     * @param array $fields An array of fields to return from the query
     *
     * @return Query
    **/
    public function select(array $fields = []) : Query
    {
        if ($fields === []) {
            return $this;
        }
        if (!Validation::isNormalArray($fields, 1)) {
            throw new Exceptions\ParameterTypeException(
                'The parameter type for this method was incorrect, '
                .'expected a normal array'
            );
        }
        if ($this->_source !== null) {
            $headers = $this->_source->getHeaders();
            if (Validation::areArraysDifferent($fields, $headers)) {
                throw new \Exception(
                    'One or more of '.implode(', ', $fields) . ' is not in ' .
                    implode(', ', $headers)
                );
            }
        }
        $this->_fields = array_flip($fields);
        return $this;
    }
    /**
     * Condition for the query to execute on the SourceFile
     *
     * @param string $condition A condition to execute
     *
     * @return Query
    **/
    public function condition(string $condition) : Query
    {
        $condition = strtoupper($condition);
        if (!Validation::validCondition($condition)) {
            throw new Exceptions\InvalidValueException(
                "Condition invalid, must be one of : \n"
                .implode(",\n", Validation::CONDITIONS)
            );
        }
        $this->_condition = $condition;
        return $this;
    }
    /**
     * Which field to use to match the condition and value against
     *
     * @param string $field      The field to test the condition/value against
     * @param string $dateFormat If the field is date, the format for this
     *
     * @return Query
    **/
    public function where(string $field, $dateFormat = null) : Query
    {
        $this->_where = $field;
        if ($dateFormat !== null) {
            $this->_dateFormat = $dateFormat;
        }
        return $this;
    }
    /**
     * The value for the condition against the where
     *
     * @param mixed  $value      The value for the condition to use against the where
     * @param string $dateFormat If the value is date, the format for this
     *
     * @return Query
    **/
    public function value($value, $dateFormat = null) : Query
    {
        $valid = false;
        if ($dateFormat !== null) {
            // if the value is an array - then we're doing a range
            if (Validation::isNormalArray($value, 2)) {
                $value = array_map(
                    function ($val) use ($dateFormat) {
                        return Validation::getDateTime($val, $dateFormat);
                    },
                    $value
                );
                if (!in_array(false, $value)) {
                    $this->_value = $value;
                    $valid = true;
                }
            } elseif (is_string($value)) {
                $datetime = Validation::getDateTime($value, $dateFormat);
                if ($datetime) {
                    $this->_value = $datetime;
                    $valid = true;
                }
            }
            if (!$valid) {
                throw new Exceptions\InvalidDateValueException(
                    'Couldn\'t create datetime object from value/dateFormat '
                    .'- please check'
                );
            }
        } elseif (is_numeric($value)) {
            $this->_value = (float) $value;
        } else {
            $this->_value = $value;
        }
        return $this;
    }

    /**
     * Limits the amount of results from the query
     * @param integer $size     The limit
     *
     * @return Query
    **/
    public function limit(int $size) : Query
    {
        $this->_limit = $size;
        return $this;
    }


    /**
     * Set the output mappings
     *
     * @param array     $mappings   The mappings to use, keyed with the original
     *
     * @return Query
    **/
    public function mappings(array $mappings) : Query
    {
        $this->_mappings = $mappings;
        return $this;
    }

    /**
     * Execute the query, returning an array of arrays, where each sub array
     * is a row of headers and values
     *
     * @param Helpers\DataInterface $outfile    a location to populate with results
     * @param assoc_array           $mappings   ['original' => 'outputheader']
     * @return array
    **/
    public function execute()
    {
        $result = [];
        $validRowCount = 0;
        Validation::openDataFile($this->_source);
        ($this->_timer) ? $this->_timer->start('execute') : null;
        // if this will be executed on a DB, then fire it off
        if ($this->_isdb) {
            $this->_source->query(
                $this->_fields,
                $this->_where,
                $this->_condition,
                $this->_value
            );
        }
        foreach ($this->_source->getNextDataRow() as $ln => $row) {
            // If this is executed on a DB it will only contain valid results
            ($this->_isdb) ? $valid = true : $valid = $this->test(trim($row[$this->_where]));
            if ($valid) {
                ++$validRowCount;
                if ($this->_fields !== []) {
                    $row = array_intersect_key($row, $this->_fields);
                }
                $this->remap($row);
                ($this->_out) ? $this->_out->writeDataRow($row) : $result[] = $row;
                if ($this->_limit > 0 && ($validRowCount === $this->_limit)) {
                    break;
                }
            }
        }
        $this->_source->close();
        $this->closeOut($result, $validRowCount);
        return $result;
    }
    /**
     * Checks to see if a row value is in query values
     *
     * @param string $rValue   The value in the row we want to check
     * @param array  $queryValue The accepted values for rValue
     *
     * @return bool
    **/
    private function _in($rValue, $queryValue)
    {
        if (gettype($queryValue) !== 'array') {
            $queryValue = str_getcsv($queryValue);
        }
        $queryValue = array_map('trim', $queryValue);
        return in_array($rValue, $queryValue);
    }
    /**
     * Checks to see if the rValue is in the query value (equiv to %like%)
     *
     * @param string $rValue   The value in the row we want to check
     * @param string $queryValue The global string we want to see if row value is in
     *
     * @return bool
    **/
    private function _contains($rValue, $queryValue)
    {
        return false !== strpos($rValue, $queryValue);
    }
    /**
     * Equality checks between the rValue and queryValue
     *
     * @param string $operator   The comparison to perform
     * @param string $rValue   The value in the row we want to check
     * @param string $queryValue The match value for the comparison
     *
     * @return bool
    **/
    private function _equality($operator, $rValue, $queryValue)
    {
        // if match value is numeric try and cast the rValue
        if (is_numeric($queryValue) && (false === ($rValue = (float) $rValue))) {
            return false;
        }
        $result = false;
        switch ($operator) {
            case 'LESS':
                $result = $rValue < $queryValue;
                break;
            case 'GREATER':
                $result = $rValue > $queryValue;
                break;
            case 'EQUALS':
                $result = $rValue === $queryValue;
                break;
            case 'NOT':
                $result = $rValue !== $queryValue;
                break;
        }
        return $result;
    }
    /**
     * Check to see if the string is empty or not empty
     *
     * @param string $condition Either EMPTY or NOT EMPTY
     * @param string $rValue  The value in the row we want to check
     *
     * @return bool
    **/
    private function _empty($condition, $rValue)
    {
        $result = $rValue === '';
        return ($condition === 'EMPTY') ? $result : !$result;
    }
    /**
     * Completes a comparison between a query date and a row date
     *
     * @param string $condition  The date comparison to carry out
     * @param string $rValue   The value being compared in the current row
     * @param mixed  $queryValue array of DateTime or binary array of DateTime
     *
     * @return bool
    **/
    private function _date($condition, $rValue, $queryValue)
    {
        $dateValue = \DateTime::createFromFormat($this->_dateFormat, $rValue);
        $result = false;

        switch ($condition) {
            case 'AFTER':
                $result = $dateValue > $queryValue;
                break;
            case 'BEFORE':
                $result = $dateValue < $queryValue;
                break;
            case 'BETWEEN':
                $result = (($dateValue > $queryValue[0]) && ($dateValue < $queryValue[1]));
                break;
            case 'NOT_BETWEEN':
                $result = (($dateValue < $queryValue[0]) || ($dateValue > $queryValue[1]));
                break;
            case 'ON':
                $result = $dateValue == $queryValue;
                break;
        }
        return $result;
    }

    /**
     * Run our query tests using the value we're analysing and return a bool
     *
     * @param mixed     $value  The value to test
     *
     * @return bool
    **/
    private function test($value) : bool
    {
        switch ($this->_condition) {
            case 'EQUALS':
            case 'GREATER':
            case 'LESS':
            case 'NOT':
                return $this->_equality($this->_condition, $value, $this->_value);
            case 'AFTER':
            case 'BEFORE':
            case 'ON':
            case 'BETWEEN':
            case 'NOT_BETWEEN':
                return $this->_date($this->_condition, $value, $this->_value);
            case 'EMPTY':
            case 'NOT_EMPTY':
                return $this->_empty($this->_condition, $value);
            case 'CONTAINS':
                return $this->_contains($value, $this->_value);
            case 'IN':
                return $this->_in($value, $this->_value);
        }
    }

    /**
     * Remaps the field names for the given row
     *
     * @param array     &$row   The current row
     *
     * @return void
    **/
    private function remap(array &$row)
    {
        if ($this->_mappings) {
            foreach ($row as $header => $value) {
                // if the mappings are not equal, then pull out the value we want
                // and unset the old value
                if (isset($this->_mappings[$header]) && $header !== $this->_mappings[$header]) {
                    $row[$this->_mappings[$header]] = $value;
                    unset($row[$header]);
                }
            }
        }
    }

    /**
     * Handles wrapping up the execution process by closing down any open outputs
     * Also handles updating the results to carry the execution data
     *
     * @param array     &$result    The result array
     * @param int       $rows       A valid row count
     *
     * @return null
    **/
    private function closeOut(array &$result, int $rows)
    {
        if ($this->_out) {
            switch ($this->_out->getType()) {
                case 'stream':
                    $this->_out->flushBuffer();
                    $this->_out->reset();
                    $result = stream_get_contents($this->_out->_fp);
                    $this->_out->close();
                    break;
                case 'file':
                    $this->_out->close();
                    $result = ['rows' => $rows];
                    break;
            }
        }
        if ($this->_timer) {
            $time = $this->_timer->stop('execute');
            $result = [
                'data' => $result,
                'timer' => [
                    'elapsed' => $time->getDuration(), // milliseconds
                    'memory' => $time->getMemory() // bytes
                ]
            ];
        }
    }
}
