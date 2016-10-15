<?php
/**
 * Interface for all data files
 *
 * @package DataCruncher
 * @subpackage Helpers
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
declare(strict_types=1);
namespace mfmbarber\DataCruncher\Helpers\Interfaces;

interface DataInterface
{
    public function setSource(string $location, array $properties);
    public function getSourceName() : string;
    public function getNextDataRow() : array;
    public function writeDataRow(array $row);

}
