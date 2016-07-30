<?php
namespace mfmbarber\Data_Cruncher\Segmentation;
use mfmbarber\Data_Cruncher\Config\Validation as Validation;
use mfmbarber\Data_Cruncher\Helpers\DataInterface as DataInterface;
use mfmbarber\Data_Cruncher\Exceptions;

class Query
{
    private $_sourceFile = null;
    private $_fields = [];
    private $_where = '';
    private $_condition = '';
    private $_value = '';
    private $_converter = null;

    public function setConverter(ConverterInterface $converter) {
        $this->_converter = $converter;
        return $this;
    }
    /**
     * Sets the data source for the query
     *
     * @param DataInterface $sourceFile The data source for the query
     *
     * @return Query
    **/
    public function fromSource(DataInterface $sourceFile)
    {
        $this->_sourceFile = $sourceFile;
        return $this;
    }
    /**
     * Select the fields to return from the SourceFile
     *
     * @param array $fields An array of fields to return from the query
     *
     * @return Query
    **/
    public function select($fields)
    {
        if (!Validation::isNormalArray($fields, 1)) {
            throw new Exceptions\ParameterTypeException(
                'The parameter type for this method was incorrect, '
                .'expected a normal array');
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
    public function condition($condition)
    {
        $condition = strtoupper($condition);
        if (!Validation::validCondition($condition)) {
            throw new Exceptions\InvalidValueException(
                "Condition invalid, must be on of : \n"
                .implode(",\n", Validation::$conditions)
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
    public function where($field, $dateFormat = null)
    {
        if (!is_string($field)) {
            throw new Exceptions\ParameterTypeException(
                'The parameter type for this method was incorrect, '
                .'expected a string field name');
        }
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
    public function value($value, $dateFormat = null)
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
        } else{
            $this->_value = $value;
        }
        return $this;
    }

    /**
     * Execute the query, returning an array of arrays, where each sub array
     * is a row of headers and values
     *
     * @param Helpers\DataInterface $outfile a location to populate with results
     *
     * @return array
    **/
    public function execute(DataInterface $outfile = null)
    {
        $result = [];
        $validRowCount = 0;
        try {
            $this->_sourceFile->open();
        } catch (Exceptions\FilePointerExistsException $e){
            // The stream is already open
        }
        if ($outfile !== null) {
            try {
                $outfile->open();
            } catch (Exceptions\FilePointerExistsException $e){
                // The stream is already open
            }
            $outfile->writeDataRow(array_keys($this->_fields));
        }
        while ([] !== ($row = $this->_sourceFile->getNextDataRow())) {
            $valid = false;
            $rowValue = trim($row[$this->_where]);
            switch ($this->_condition) {
            case 'EQUALS':
            case 'GREATER':
            case 'LESS':
            case 'NOT':
                $valid = $this->_equality(
                    $this->_condition, $rowValue, $this->_value
                );
                break;
            case 'AFTER':
            case 'BEFORE':
            case 'ON':
            case 'BETWEEN':
            case 'NOT_BETWEEN':
                $valid = $this->_date(
                    $this->_condition, $rowValue, $this->_value
                );
                break;
            case 'EMPTY':
            case 'NOT_EMPTY':
                $valid = $this->_empty($this->_condition, $rowValue);
                break;
            case 'CONTAINS':
                $valid = $this->_contains($rowValue, $this->_value);
                break;
            case 'IN':
                $valid = $this->_in($rowValue, $this->_value);
                break;
            }
            if ($valid) {
                $validRowCount++;
                if ($outfile === null) {
                    $result[] = array_intersect_key($row, $this->_fields);
                } else {
                    $outfile->writeDataRow(
                        array_intersect_key($row, $this->_fields)
                    );
                }
            }
        }
        $this->_sourceFile->close();
        if ($outfile !== null) {
            $outfile->close();
            return $validRowCount;
        } else {
            return $result;
        }
    }
    /**
     * Checks to see if a row value is in query values
     *
     * @param string $rowValue   The value in the row we want to check
     * @param array  $queryValue The accepted values for rowValue
     *
     * @return bool
    **/
    private function _in($rowValue, $queryValue)
    {
        if (gettype($queryValue) !== 'array') {
            $queryValue = str_getcsv($queryValue);
        }
        $queryValue = array_map('trim', $queryValue);
        return in_array($rowValue, $queryValue);
    }
    /**
     * Checks to see if the rowValue is in the query value (equiv to %like%)
     *
     * @param string $rowValue   The value in the row we want to check
     * @param string $queryValue The global string we want to see if row value is in
     *
     * @return bool
    **/
    private function _contains($rowValue, $queryValue)
    {
        return false !== strpos($rowValue, $queryValue);
    }
    /**
     * Equality checks between the rowValue and queryValue
     *
     * @param string $operator   The comparison to perform
     * @param string $rowValue   The value in the row we want to check
     * @param string $queryValue The match value for the comparison
     *
     * @return bool
    **/
    private function _equality($operator, $rowValue, $queryValue)
    {
        // if match value is numeric try and cast the rowValue
        if (is_numeric($queryValue)
            && (false === ($rowValue = (float) $rowValue))
        ) {
            return false;
        }
        $result = false;
        switch ($operator) {
            case 'LESS':
                $result = $rowValue < $queryValue;
                break;
            case 'GREATER':
                $result = $rowValue > $queryValue;
                break;
            case 'EQUALS':
                $result = $rowValue === $queryValue;
                break;
            case 'NOT':
                $result = $rowValue !== $queryValue;
                break;
        }
        return $result;
    }
    /**
     * Check to see if the string is empty or not empty
     *
     * @param string $condition Either EMPTY or NOT EMPTY
     * @param string $rowValue  The value in the row we want to check
     *
     * @return bool
    **/
    private function _empty($condition, $rowValue)
    {
        $result = $rowValue === '';
        if ($condition === 'EMPTY') {
            return $result;
        } else {
            return !$result;
        }
    }
    /**
     * Completes a comparison between a query date and a row date
     *
     * @param string $condition  The date comparison to carry out
     * @param string $rowValue   The value being compared in the current row
     * @param mixed  $queryValue array of DateTime or binary array of DateTime
     *
     * @return bool
    **/
    private function _date($condition, $rowValue, $queryValue)
    {
        $dateValue = \DateTime::createFromFormat($this->_dateFormat, $rowValue);
        $result = false;

        switch ($condition) {
            case 'AFTER':
                $result = $dateValue > $queryValue;
                break;
            case 'BEFORE':
                $result = $dateValue < $queryValue;
                break;
            case 'BETWEEN':
                $result = (($dateValue > $queryValue[0])
                            && ($dateValue < $queryValue[1]));
                break;
            case 'NOT_BETWEEN':
                $result = (($dateValue < $queryValue[0])
                            || ($dateValue > $queryValue[1]));
                break;
            case 'ON':
                $result = $dateValue == $queryValue;
                break;
        }
        return $result;
    }

}
