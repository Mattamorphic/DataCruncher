<?php
namespace mfmbarber\CSV_Cruncher\Helpers;

interface DataInterface
{
    public function setSource($location, $properties);
    public function getSourceName();
    public function getNextDataRow();
    public function writeDataRow($row);
    //public function sendRaw($type, $data);
    // public function connect();
    // public function disconnect();
    // public function restart();
    // public function open();
    // public function close();
    // public function reset();

}
