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

use Symfony\Component\Stopwatch\Stopwatch;
use mfmbarber\DataCruncher\Config\Validation as Validation;
use mfmbarber\DataCruncher\Helpers\Interfaces\DataInterface as DataInterface;
use mfmbarber\DataCruncher\Exceptions;

class Query
{
    private $_source = null;
    private $_isdb = false;
    private $_fields = [];
    private $_where = '';
    private $_condition = '';
    private $_value = '';
    private $_limit = -1;

    /**
     * Sets the data source for the query
     *
     * @param DataInterface $source The data source for the query
     *
     * @return Query
    **/
    public function fromSource(DataInterface $source)
    {
        $this->_source = $source;
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
    public function select(array $fields) : Query
    {
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
     * Execute the query, returning an array of arrays, where each sub array
     * is a row of headers and values
     *
     * @param Helpers\DataInterface $outfile    a location to populate with results
     * @param assoc_array           $mappings   ['original' => 'outputheader']
     * @return array
    **/
    public function execute(DataInterface $outfile = null, $mappings = null, bool $timer = false)
    {
        $stopwatch = new Stopwatch();
        $result = [];
        $validRowCount = 0;
        if ($outfile !== null) {
            Validation::openDataFile($outfile, true);
        }
        Validation::openDataFile($this->_source);
        ($timer) ? $stopwatch->start('execute') : null;
        // if this will be executed on a DB, then fire it off
        if ($this->_isdb) {
            $this->_source->query($this->_fields, $this->_where, $this->_condition, $this->_value);
        }
        foreach ($this->_source->getNextDataRow() as $row) {
            // If this is executed on a DB it will only contain valid results
            if ($this->_isdb) {
                $valid = true;
            } else {
                $valid = false;
                $rValue = trim($row[$this->_where]);
                switch ($this->_condition) {
                    case 'EQUALS':
                    case 'GREATER':
                    case 'LESS':
                    case 'NOT':
                        $valid = $this->_equality($this->_condition, $rValue, $this->_value);
                        break;
                    case 'AFTER':
                    case 'BEFORE':
                    case 'ON':
                    case 'BETWEEN':
                    case 'NOT_BETWEEN':
                        $valid = $this->_date($this->_condition, $rValue, $this->_value);
                        break;
                    case 'EMPTY':
                    case 'NOT_EMPTY':
                        $valid = $this->_empty($this->_condition, $rValue);
                        break;
                    case 'CONTAINS':
                        $valid = $this->_contains($rValue, $this->_value);
                        break;
                    case 'IN':
                        $valid = $this->_in($rValue, $this->_value);
                        break;
                }
            }
            if ($valid) {
                $validRowCount++;
                $row = array_intersect_key($row, $this->_fields);
                if (null !== $mappings) {
                    foreach ($row as $header => $value) {
                        // if the mappings are not equal, then pull out the value we want
                        // and unset the old value
                        if (isset($mappings[$header]) && $header !== $mappings[$header]) {
                            $row[$mappings[$header]] = $value;
                            unset($row[$header]);
                        }
                    }
                }
                ($outfile) ? $outfile->writeDataRow($row) : $result[] = $row;
                if ($this->_limit > 0 && ($validRowCount === $this->_limit)) {
                    break;
                }
            }
        }
        $this->_source->close();
        if ($outfile) {
            switch ($outfile->getType()) {
                case 'stream':
                    $outfile->flushBuffer();
                    $outfile->reset();
                    $result = stream_get_contents($outfile->_fp);
                    $outfile->close();
                    break;
                case 'file':
                    $outfile->close();
                    $result = ['rows' => $validRowCount];
                    break;
            }
        }
        if ($timer) {
            $time = $stopwatch->stop('execute');
            $result = [
                'data' => $result,
                'timer' => [
                    'elapsed' => $time->getDuration(), // milliseconds
                    'memory' => $time->getMemory() // bytes
                ]
            ];
        }
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
}
