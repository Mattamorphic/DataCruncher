<?php
/**
 * DataSource
 *
 * @package DataCruncher
 * @subpackage Helpers
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
declare(strict_types=1);
namespace mfmbarber\DataCruncher\Helpers;

use mfmbarber\DataCruncher\Helpers\{Files, Databases, System};
class DataSource
{
    /**
     * Generates a source object based on format and type using a Factory pattern
     * @param string $format    Denotes the overall format, file, system, external
     * @param string $type      Denotes the contextual type, JSON, CSV, XML
     *
     * @return new Object
     *
    **/
    public static function generate(
        string $format,
        string $type,
        string $node = null,
        string $parent = null
    ) {
        switch ($format) {
            case 'file':
                return self::generateFile($type, $node, $parent);
            case 'db':
                return self::generateDB($type);
            case 'system':
                return self::generateSystemOutput($type);
        }
    }

    /**
     * If the Factory requires a file, this method is used to generate and return this
     *
     * @return DataInterface
    **/
    public static function generateFile(
        string $type,
        string $node = null,
        string $parent = null
    ) {
        switch ($type) {
            case 'csv':
                return new Files\CSVFile();
            case 'xml':
                return new Files\XMLFile($node, $parent);
        }
    }

    /**
     * If the Factory requires a db, this method is used to generate and return this
     *
     * @return DataInterface
    **/
    public static function generateDB(string $type)
    {
        // for now
        $type = 'sql';
        switch ($type) {
            case 'sql':
                return new Databases\Database();
        }
    }

    /**
     * If the Factory requires a system output, this method is used to generate and return this
     *
     * @return DataInterface
    **/
    public static function generateSystemOutput(string $type)
    {
        switch ($type) {
            case 'csv':
                return new System\CSV();
        }
    }
}
