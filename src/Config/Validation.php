<?php
/**
 * Validation and Reusable Functions
 *
 * @package Data_Cruncher
 * @subpackage Config
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
namespace mfmbarber\Data_Cruncher\Config;

use mfmbarber\Data_Cruncher\Helpers\DataInterface as DataInterface;
use mfmbarber\Data_Cruncher\Exceptions as CSV_Exceptions;

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
     * @param mixed $arr     The array to check (this is type hinted)
     * @param int   $minSize The minimum size for the array - this defaults to 0
     *
     * @return bool
    **/
    public static function isNormalArray($arr, $minSize = 1)
    {
        return self::isArray($arr, $minSize) && (bool) !count(array_filter(array_keys($arr), 'is_string'));
    }
    /**
     * Checks to see if this an associative array and not an integer array
     * @param mixed $arr     The array to check (this is type hinted)
     * @param int   $minSize The minimum size for the array - this defaults to 0
     *
     * @return bool
    **/
    public static function isAssociativeArray($arr, $minSize = 1)
    {
        return self::isArray($arr, $minSize) && (count(array_filter(array_keys($arr), 'is_string')) > 0);
    }
    /**
     *
     *
    **/
    public static function isArray($arr, $minSize = 1)
    {
        return is_array($arr) && (count($arr) >= $minSize);
    }
    /**
     * Is the given condition a valid condition
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
     * @return mixed
    **/
    public static function getDateTime($value, $dateFormat)
    {
        $dateFormat = trim($dateFormat);
        // If they just need the year then assume from 01/01 of year
        if ($dateFormat === 'Y' || $dateFormat === 'YY' || $dateFormat === 'YYYY') {
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
    /**
     * Given a data file object, open this - by reference
     *
     * @param DataInterface &$file          The file to open
     * @param string        $node_name      The name of the nodes we want to look at
     * @param string        $start_element  The name of the element that is at the root
     *
     * @return void
     */
    public static function openDataFile(DataInterface &$file, $write = false)
    {
        try {
            if (false !== strpos(get_class($file), 'XMLFile')) {
                $file->open(!$write);
            } else {
                $file->open();
            }
        } catch (Exceptions\FilePointerExistsException $e) {
            // The stream is already open
        }
    }
}
