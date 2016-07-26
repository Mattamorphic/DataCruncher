<?php
namespace mfmbarber\CSV_Cruncher\Config;
use mfmbarber\CSV_Cruncher\Exceptions as CSV_Exceptions;

class Validation
{
    public static $conditions = [
            'EQUALS',
            'GREATER',
            'LESS',
            'NOT',
            'AFTER',
            'BEFORE',
            'ON',
            'BETWEEN',
            'NOT_BETWEEN',
            'EMPTY',
            'NOT_EMPTY',
            'CONTAINS',
            'IN'
    ];
    /**
     * Checks to see if this is a normal numerical array and not associative
     * @param array $arr     The array to check (this is type hinted)
     * @param int   $minSize The minimum size for the array - this defaults to 0
     *
     * @return bool
    **/
    public static function isNormalArray(array $arr, $minSize=0)
    {
        return (self::isArray($arr, $minSize)) && count(array_filter(array_keys($arr), 'is_string')) == 0);
    }
    /**
     * Checks to see if this an associative array and not an integer array
     * @param array $arr     The array to check (this is type hinted)
     * @param int   $minSize The minimum size for the array - this defaults to 0
     *
     * @return bool
    **/
    public static function isAssociativeArray(array $arr, $levels = 1)
    {
        return (self::isArray($arr, $minSize)) && (count(array_filter(array_keys($arr), 'is_string')) > 0);
    }
    /**
     *
     *
    **/
    public static function isArray($arr, $minSize=0)
    {
        return (is_array($arr)) && (count($arr) >= $minSize);
    }
    /**
     *
     *
    **/
    public static function validCondition($cond)
    {
        return (in_array($cond, self::$conditions));
    }
    /**
     * Given a value and a dateFormat, return a DateTime obj
     *
     * @param string $value      The date time value we want to use as the basis
     * @param string $dateFormat The format of our value
     *
     * @return bool
    **/
    public static function getDateTime($value, $dateFormat)
    {
        $dateFormat = trim(strtoupper($dateFormat));
        // If they just need the year then assume from 01/01 of year
        if ($dateFormat === 'Y' || $dateFormat === 'YY') {
            $dateObj = new \DateTime();
            try {
                $dateObj->setDate($value, 1, 1);
            } catch (\Exception $e) {
                $dateObj = false;
            }
        } else {
            $dateObj = \DateTime::createFromFormat($dateFormat, $value);
        }
        return $dateObj;
    }
}
