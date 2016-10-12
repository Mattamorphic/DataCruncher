<?php
/**
 * Interface for all data files
 *
 * @package Data_Cruncher
 * @subpackage Helpers
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
declare(strict_types=1);
namespace mfmbarber\Data_Cruncher\Helpers\Interfaces;

interface DataInterface
{
    public function setSource(string $location, array $properties);
    public function getSourceName() : string;
    public function getNextDataRow() : array;
    public function writeDataRow(array $row);

}
