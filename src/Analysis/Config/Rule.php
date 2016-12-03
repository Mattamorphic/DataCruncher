<?php
/**
 * Rule Processor
 *
 * @package DataCruncher
 * @subpackage Analysis
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
declare(strict_types=1);
namespace mfmbarber\DataCruncher\Analysis\Config;

use mfmbarber\DataCruncher\Config\Validation;
use mfmbarber\DataCruncher\Helpers\Interfaces\DataInterface;
use mfmbarber\DataCruncher\Analysis\Config\Rule;
use mfmbarber\DataCruncher\Exceptions;

class Rule
{
    private $_field;
    private $_function;
    private $_option;
    private $_label = null;

  /**
     * Sets the field to calculate statistics on
     *
     * @param string $field The name of the field to run the statistics on
     *
     * @return Rule
    **/
    public function setField(string $field) : Rule
    {
        $this->_field = $field;
        return $this;
    }

    public function setLabel(string $label) : Rule
    {
        $this->_label = $label;
        return $this;
    }

    /**
     * Sets the _function private property to be a closure, this closure
     * simply returns the value given. Exact implies the key in the results
     * will be exact and not a grouping.
     *
     * @return Rule
    **/
    public function groupExact() : Rule
    {
        $this->_function = function ($value, $option) {
            return $value;
        };
        return $this;
    }

    /**
     * Sets the _function private property to be a closure, this closure
     * returns the numeric grouping given the step for the groups.
     * So for instance a step of 10, would return 0, 10, if the value given
     * was 7
     *
     * @param integer $step The step between each value in the grouping
     *
     * @return Rule
    **/
    public function groupNumeric(int $step) : Rule
    {
        $this->_option = $step;
        $this->_function = function($value, $step) {
            $lower = ((int) ($value / $step)) * $step;
            $upper = (((int) ($value / $step)) + 1) * $step;
            return "$lower, $upper";
        };
        return $this;
    }

    /**
     * Sets the _function private property to a closure, this closure
     * returns the result of a regex expression. i.e. '/^([\w\-]+)/i'
     *
     * @param string $regex i.e. '/^([\w\-]+)/i'
     *
     * @return Rule
    **/
    public function groupRegex($regex) : Rule
    {
        $this->_option = $regex;
        $this->_function = function ($value, $regex) {
            $result = [];
            preg_match($regex, trim($value), $result, PREG_OFFSET_CAPTURE);
            return $result[0][0];
        };
        return $this;
    }
    /**
     * Sets the _function private property to be a closure, this closure
     * returns the date grouping given the part of the date to be returned
     * So for instance, given a returnFormat of 'Y', would return 1987 given
     * 24/11/1987 and a dataFormat of d/m/Y
     *
     * @param string $dataFormat   The format that the data is in in the source
     * @param string $returnFormat The format to return the data in
     *
     * @return Rule
    **/
    public function groupDate(string $dataFormat, string $returnFormat) : Rule
    {
        $this->_option = $returnFormat;
        $this->_dataFormat = $dataFormat;
        $this->_function = function ($value, $format) {
            $date = Validation::getDateTime($value, $this->_dataFormat);
            if (!$date) {
                return false;
            }
            return $date->format($format);
        };
        return $this;
    }

    /**
     * Returns the rule as a structured array
     *
     * @return object
    **/
    public function get()
    {
        return (object)[
            'field' => $this->_field,
            'func' => $this->_function,
            'option' => $this->_option,
            'label' => $this->_label
        ];
    }
}
