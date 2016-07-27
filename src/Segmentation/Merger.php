<?php
namespace mfmbarber\Data_Cruncher\Segmentation;
use mfmbarber\Data_Cruncher\Config\Validation as Validation;
use mfmbarber\Data_Cruncher\Helpers\DataInterface as DataInterface;
use mfmbarber\Data_Cruncher\Exceptions;
class Merger
{
    private $_sources = [];
    public function __construct()
    {

    }
    public function fromSource(DataInterface $source)
    {
        $this->_sources[] = $source;
        return $this;
    }
    public function on($field)
    {
        // if (!Validation::isNormalArray($fields, 1)) {
        //     throw new Exceptions\ParameterTypeException(
        //         'The parameter type for this method was incorrect, '
        //         .'expected a normal array');
        // }
        $this->_field =  $field;
        return $this;
    }
    public function deduplicate()
    {
        return $this;
    }
    public function execute(DataInterface $output = null)
    {
        // TODO : Check to see if fields are in the source and merge
        foreach ($this->_sources as $source) {
            try {
                $source->open();
            } catch (Exceptions\FilePointerExistsException $e){
                $source->reset();
            }
        }
        $result = [];
        while (count($this->_sources) > 0) {
            $analyse = array_shift($this->_sources);
            while ([] !== ($row = $analyse->getNextDataRow())) {
                foreach ($this->_sources as $source) {
                    while ([] !== ($merge_row = $source->getNextDataRow())) {
                        //inner join
                        if ($row[$this->_field] === $merge_row[$this->_field]) {
                            $result[] = array_merge($row, $merge_row);
                        }
                    }
                    $source->reset();
                }
            }
            $analyse->close();
        }

        return $result;
    }


}
