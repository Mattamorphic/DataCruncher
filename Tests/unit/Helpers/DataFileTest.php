<?php
namespace mfmbarber\Data_Cruncher\Tests\Unit\Helpers;
use mfmbarber\Data_Cruncher\Tests\Mocks as Mocks;
use mfmbarber\Data_Cruncher\Helpers\CSVFile as CSVFile;

class CSVFileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that once assigned the source name can be retrieved
     *
     * @test
     *
     * @return null
     **/
    public function getSourceNameWorksCorrectly()
    {
        $customMocks = new Mocks();
        $sourceFile = $customMocks->createMockSourceFile("test\ntest");
        $this->assertEquals(
            $sourceFile->getSourceName(),
            'php://memory',
            'Name isn\'t set correctly'
        );

    }
    /**
     * Unit test, retrieving associative array of headers & values from a
     * data stream
     *
     * @test
     *
     * @return null
     **/
    public function getNextRowsCorrectly()
    {

        $data = "email, name, colour, dob, age\n"
        ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
        ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
        ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25";
        $customMocks = new Mocks();
        $sourceFile = $customMocks->createMockSourceFile($data);
        $result = [];
        while ([] !== ($row = $sourceFile->getNextDataRow())) {
            $result[] = $row;
        }
        $sourceFile->close();
        $this->assertEquals(
            [
                [
                    'email' => 'mfmbarber@test.com',
                    'name' => 'matt',
                    'colour' => 'black, green, blue',
                    'dob' => '24/11/1987',
                    'age' => '28'
                ],
                [
                    'email' => 'matt.barber@test.com',
                    'name' => 'matthew',
                    'colour' => 'red, green',
                    'dob' => '01/12/1980',
                    'age' => '35'
                ],
                [
                    'email' => 'tony.stark@avengers.com',
                    'name' => 'tony',
                    'colour' => 'red, gold',
                    'dob' => '02/05/1990',
                    'age' => '25'
                ]
            ],
            $result
        );
    }
    /**
     * Unit test, Either a file doesn't exist, or isn't readable - throws
     * matching exception with message
     *
     * @test
     * @expectedException        mfmbarber\Data_Cruncher\Exceptions\InvalidFileException
     * @expectedExceptionMessage FakeFile.csv doesn't exist
     *
     * @return null
    **/
    public function invalidFileException()
    {
        $sourceFile = new CSVFile();
        $sourceFile->setSource('FakeFile.csv', ['modifier' => 'r']);
    }
    /**
     * Unit test, If a file pointer hasn't been opened and a close is attempted
     *
     * @test
     * @expectedException        mfmbarber\Data_Cruncher\Exceptions\FilePointerInvalidException
     * @expectedExceptionMessage The filepointer is null on this object, use CSVFile::open to open a new filepointer
     *
     * @return null
    **/
    public function closeInvalidFilePointerException()
    {
        $sourceFile = new CSVFile();
        $sourceFile->close();
    }
    /**
     * Unit test, If a file pointer hasn't been opened and a reset is attempted
     *
     * @test
     * @expectedException        mfmbarber\Data_Cruncher\Exceptions\FilePointerInvalidException
     * @expectedExceptionMessage The filepointer is null on this object, use CSVFile::open to open a new filepointer
     *
     * @return null
    **/
    public function resetInvalidFilePointerException()
    {
        $sourceFile = new CSVFile();
        $sourceFile->reset();
    }
    /**
     * Unit test, If a file pointer hasn't been opened and a write is attempted
     *
     * @test
     * @expectedException        mfmbarber\Data_Cruncher\Exceptions\FilePointerInvalidException
     * @expectedExceptionMessage The filepointer is null on this object, use CSVFile::open to open a new filepointer
     *
     * @return null
    **/
    public function writeInvalidFilePointerException()
    {
        $sourceFile = new CSVFile();
        $sourceFile->writeDataRow(['email' => 'test@test.com']);
    }

}
