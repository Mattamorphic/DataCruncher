<?php
/**
 * Processor (Factory)
 *
 * @package Manipulator
 * @author matt barber <mfmbarber@gmail.com>
 *
**/
declare(strict_types=1);
namespace mfmbarber\DataCruncher;

use mfmbarber\DataCruncher\Config\Validation as Validator;
use mfmbarber\DataCruncher\Segmentation\{Query, Split, Merger};
use mfmbarber\DataCruncher\Analysis\{Statistics, Config};

class Processor
{
    /**
     * Generates a processor for our data based on the category and type
     * chosen to be used.
     *
     * @param string    $category   The category of processor to generate
     * @param string    $type       The type of processor in the category to generate
     *
     * @return object
     *
    **/
    public static function generate($category, $type) {
        switch ($category) {
            case 'segmentation':
                return self::generateSegmentation($type);
            case 'analysis':
                return self::generateAnalysis($type);
        }
    }

    /**
     * Generates a segementation object based on the type
     *
     * @param string    $type   The type of processor to return
     *
     * @return object
     *
    **/
    public static function generateSegmentation($type)
    {
        switch ($type) {
            case 'query':
                return new Query();
            case 'split':
                return new Split();
            case 'merge':
                return new Merger();
        }
    }


    /**
     * Generates a analysis object based on the type
     *
     * @param string    $type   The type of processor to return
     *
     * @return object
     *
    **/
    public static function generateAnalysis($type)
    {
        switch ($type) {
            case 'statistics':
                return new Statistics();
            case 'rule':
                return new Config\Rule();
        }
    }
}
