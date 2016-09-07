<?php
/**
 * DataSource
 *
 * @package Data_Cruncher
 * @subpackage Helpers
 * @author matt barber <mfmbarber@gmail.com>
 *
 */

namespace mfmbarber\Data_Cruncher\Helpers;

use mfmbarber\Data_Cruncher\Helpers\Files;
use mfmbarber\Data_Cruncher\Helpers\System;

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
    public static function generate($format, $type, $node = null, $parent = null)
    {
        switch ($format) {
            case 'file':
                return self::generateFile($type, $node, $parent);
            case 'system':
                return self::generateSystemOutput($type, $node, $parent);
        }
    }

    public static function generateFile($type, $node = null, $parent = null)
    {
        switch ($type) {
            case 'csv':
                return new Files\CSVFile();
            case 'xml':
                return new Files\XMLFile($node, $parent);
        }
    }

    public static function generateSystemOutput($type, $node = null, $parent = null)
    {
        switch ($type) {
            case 'csv':
                return new System\CSVOutput();
        }
    }
}
