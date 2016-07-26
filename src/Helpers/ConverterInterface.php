<?php
namespace mfmbarber\CSV_Cruncher\Helpers;

interface ConverterInterface
{
    public static function convertArray($array);
    public static function convertData($dataSource);

}
