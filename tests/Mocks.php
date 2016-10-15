<?php
namespace mfmbarber\DataCruncher\Tests;

class Mocks extends \PHPUnit_Framework_TestCase
{
   /**
    * Creates a MOCK of the Query class
    *
    * @return Query
    **/
    public function createMockQuery()
    {
        $query = $this->getMockBuilder(
            'mfmbarber\DataCruncher\Segmentation\Query'
        )->setMethods(['execute'])->getMock();
        $query->method('execute')->willReturn(
            [
                ['name' => 'matthew', 'age' => 35],
                ['name' => 'tony', 'age' => 25]
            ]
        );
        return $query;
    }
}
