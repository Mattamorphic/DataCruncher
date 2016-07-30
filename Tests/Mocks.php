<?php
namespace mfmbarber\Data_Cruncher\Tests;

class Mocks extends \PHPUnit_Framework_TestCase
{
    /**
     * Creates a MOCK of the sourceFile class using the php://memory stream
     * instead of a file. Data provided is a CSV string to convert to a sourceFile
     *
     * @param string $data A valid CSV string, comma delimiter and " encloser
     *
     * @return SourceFile
    **/
    public function createMockSourceFile($data)
    {
        $sourceFile = $this->getMockBuilder(
            'mfmbarber\Data_Cruncher\Helpers\CSVFile'
        )->setMethods(['fileExists', 'readable'])->getMock();
        $sourceFile->method('readable')->willReturn(true);
        $sourceFile->method('fileExists')->willReturn(true);
        $sourceFile->setSource('php://memory', ['modifier' => 'r']);
        // Setup mocked data stream
        $sourceFile->open();
        foreach (explode("\n", $data) as $line) {
            $sourceFile->writeDataRow(str_getcsv($line, ',', '"'));
        }
        $sourceFile->reset();
        // Setup complete
        return $sourceFile;
    }
    /**
     * Creates a MOCK of the OutputFile class using the php://memory stream
     * instead of a file. Data provided is a CSV string to convert to a sourceFile
     *
     * @return SourceFile
    **/
    public function createMockOutFile()
    {
        $outFile = $this->getMockBuilder(
            'mfmbarber\Data_Cruncher\Helpers\CSVFile'
        )->setMethods(['writable'])->getMock();
        $outFile->method('writable')->willReturn(true);
        $outFile->setSource('php://temp', ['modifier' => 'w']);
        return $outFile;
    }
    /**
    * Creates a MOCK of the Query class
    *
    * @return Query
    **/
    public function createMockQuery()
    {
        $query = $this->getMockBuilder(
            'mfmbarber\Data_Cruncher\Segmentation\Query'
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
