<?php
/**
 * Interface for all data files
 *
 * @package Data_Cruncher
 * @subpackage Helpers
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
namespace mfmbarber\Data_Cruncher\Helpers;

interface DataInterface
{
    public function setSource($location, array $properties);
    public function getSourceName();
    public function getNextDataRow();
    public function writeDataRow(array $row);
    //public function sendRaw($type, $data);
    // public function connect();
    // public function disconnect();
    // public function restart();
    // public function open();
    // public function close();
    // public function reset();

}
