<?php
/**
 * Validation and Reusable Functions
 *
 * @package DataCruncher
 * @subpackage Config
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
declare(strict_types=1);
namespace mfmbarber\DataCruncher\Config;

use mfmbarber\DataCruncher\Helpers\Interfaces\DataInterface as DataInterface;
use mfmbarber\DataCruncher\Exceptions as CSV_Exceptions;

class Validation
{
    const CONDITIONS = [
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
    public static function isNormalArray($arr, int $minSize = 1) : bool
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
    public static function isAssociativeArray($arr, int $minSize = 1) : bool
    {
        return self::isArray($arr, $minSize) && (count(array_filter(array_keys($arr), 'is_string')) > 0);
    }
    /**
     *
     *
    **/
    public static function isArray($arr, int $minSize = 1) : bool
    {
        return is_array($arr) && (count($arr) >= $minSize);
    }
    /**
     * Is the given condition a valid condition
     *
    **/
    public static function validCondition(string $cond) : bool
    {
        return (in_array($cond, self::CONDITIONS));
    }
    /**
     * Given a value and a dateFormat, return a DateTime obj
     *
     * @param string $value      The date time value we want to use as the basis
     * @param string $dateFormat The format of our value
     *
     * @return mixed
    **/
    public static function getDateTime($value, string $dateFormat)
    {
        $dateFormat = trim($dateFormat);
        // If they just need the year then assume from 01/01 of year
        if ($dateFormat === 'Y' || $dateFormat === 'YY' || $dateFormat === 'YYYY') {
            $dateObj = new \DateTime();
            if (is_numeric($value)) {
                $dateObj->setDate((int) $value, 1, 1);
            } else {
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
    public static function openDataFile(DataInterface &$file, bool $write = false)
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

    /**
     * Converts an array of arrays to a CSV string
    **/
    public static function arrayToCSV(array $arr, string $delimiter = ',', string $encloser = '"') : string
    {
        $temp = fopen('php://temp', 'rw');
        foreach ($arr as $row) {
            fputcsv($temp, $row, $delimiter, $encloser);
        }
        rewind($temp);
        $csv = stream_get_contents($temp);
        fclose($temp);
        return $csv;
    }

    /**
     * Compares the values in two different arrays, to see if arr1 is in arr2
     * @param array     $arr1
     * @param array     $arr2
     *
     * @return bool
    **/
    public static function areArraysDifferent(array $arr1, array $arr2) : bool
    {
        return count(array_diff($arr1, $arr2)) ? true : false;
    }

    /**
     * Delete files from a directory - unless there extension exists in array
     * @param string    $dir                    The directory to delete the files from
     * @param array     $dontDeleteExtensions   Don't delete files with these extensions
     *
     * @return null
    **/
    public static function deleteFiles(string $dir, array $dontDeleteExtensions = [])
    {
        if (false !== ($files = scandir($dir))) {
            array_map(
                function ($file) use ($dir, $dontDeleteExtensions) {
                    if (strlen($file) <= 2) {
                        return;
                    }
                    $file = strtolower("$dir/$file");
                    if (is_dir($file)) {
                        return;
                    }
                    if (!in_array(pathinfo($file, PATHINFO_EXTENSION), $dontDeleteExtensions)) {
                        @unlink($file);
                    }
                },
                $files
            );
        }
    }

    /**
     * Completes a multineed stripos across a haystack string
     *
     * @param string    $haystack   The string to scan
     * @param array     $needles    The characters to check for
     *
     * @return array
    **/
    public static function multiStripos(string $haystack, array $needles, bool $filter = false)
    {
        $found = array_flip($needles);
        array_walk(
            $found,
            function (&$needle, $key) use ($haystack) {
                $needle = stripos($haystack, $key);
            }
        );
        return ($filter) ? count(array_filter(array_values($found), function ($item) { return $item !== false; })) > 0 : $found;
    }
}
