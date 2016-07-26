<?php
namespace mfmbarber\Data_Cruncher\Segmentation;
use mfmbarber\Data_Cruncher\Exceptions as Exceptions;
use mfmbarber\Data_Cruncher\Segmentation\Query as Query;

class Split
{
    public function __construct()
    {

    }
    public function fromSource($source)
    {
        $this->_source = $source;
        return $this;
    }
    public function horizontal($size)
    {
        $this->_direction = 'HORIZONTAL';
        $this->_size = $size;
        return $this;
    }
    public function vertical($groupings = [])
    {
        $this->_direction = 'VERTICAL';
        $this->_groups = $this->_setGroupings($groupings);
        // $this->_function = function ($row) {
        //     return array_intersect_key($row, $keys)
        // }
        return $this;
    }
    public function bilateral($groupings = [], $size)
    {
        $this->_direction = 'BILATERAL';
        // ['firstName', 'email'], ['lastName', 'email']
        $this->_groups = $this->_setGroupings($groupings);
        $this->_size = $size;
        return $this;
    }
    // public function query(Query $query)
    // {
    //     $this->_query = $query;
    //     return $this;
    // }

    public function execute($outFiles = [])
    {
        if ($this->_direction === 'VERTICAL') {
            $result = array_fill(0, count($this->_groups)-1, []);
        } else if ($this->_direction === 'BILATERAL'){
            $result = [];
            $set = array_fill(0, count($this->_groups) - 1, []);
        } else {
            $result = [];
            $set = [];
        }
        while ([] !== ($row = $this->_source->getNextDataRow())) {
            switch ($this->_direction) {
                case 'HORIZONTAL':
                    $set[] = $row;
                    if (count($set) === $this->_size) {
                        $result[] = $set;
                        $set = [];
                    }
                    break;
                case 'VERTICAL':
                    foreach($this->_groups as $idx => $group) {
                        $result[$idx][] = array_intersect_key($row, $group);
                    }
                    break;
                case 'BILATERAL':
                    foreach($this->_groups as $idx => $group) {
                        $set[$idx][] = array_intersect_key($row, $group);
                        if (count($set[$idx]) === $this->_size) {
                            $result[] = $set[$idx];
                            $set[$idx] = [];
                        }
                    }
                    break;

            }
        }
        return $result;
    }
    private function _setGroupings($groupings)
    {
        $groups = [];
        foreach ($groupings as $group) {
            $groups[] = array_flip(
                array_map('trim', explode(',', $group))
            );
        }
        return $groups;
    }
}
