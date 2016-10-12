<?php
namespace mfmbarber\Data_Cruncher\Tests\Unit\Helpers;

use mfmbarber\Data_Cruncher\Helpers\Files\CSVFile as CSVFile;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class CSVFileTest extends \PHPUnit_Framework_TestCase
{
    private $root;

    private $mockSourceCSV;

    public function setUp()
    {
        $this->root = vfsStream::setup('home', 0777);
        $file = vfsStream::url('home/test', 0777);
        file_put_contents(
            $file,
            "email, name, colour, dob, age\n"
            ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
            ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
            ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
        );
        $this->mockSourceCSV = new CSVFile();
        $this->mockSourceCSV->setSource('vfs://home/test', ['modifier' => 'r']);
    }

    public function tearDown()
    {
        $this->root = null;
        $this->mockSourceCSV = null;
    }
    /**
     * Tests that once assigned the source name can be retrieved
     *
     * @test
     *
     * @return null
     **/
    public function getSourceName()
    {
        $this->assertEquals(
            $this->mockSourceCSV->getSourceName(),
            'vfs://home/test',
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
        $result = [];
        try {
            $this->mockSourceCSV->open();
        } catch (Exceptions\FilePointerExistsException $e) {
            // The stream is already open
        }
        while ([] !== ($row = $this->mockSourceCSV->getNextDataRow())) {
            $result[] = $row;
        }
        $this->mockSourceCSV->close();
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
     * @expectedExceptionMessage The filepointer is null on this object, use class::open to open a new filepointer
     *
     * @return null
    **/
    public function closeInvalidFilePointerException()
    {
        $sourceFile = new CSVFile();
        $sourceFile->close();
    }
    /**
     * Unit test, If a file pointer hasn't been opened and a close is attempted
     *
     * @test
     * @expectedException        mfmbarber\Data_Cruncher\Exceptions\FilePointerExistsException
     * @expectedExceptionMessage A filepointer exists on this object, use class::close to close the current pointer
     *
     * @return null
    **/
    public function openInvalidFilePointerException()
    {
        $csv = new CSVFile();
        $csv->setSource('vfs://home/test', ['modifier' => 'r']);
        $csv->open();
        $csv->open();
    }
    /**
     * Unit test, If a file pointer hasn't been opened and a reset is attempted
     *
     * @test
     * @expectedException        mfmbarber\Data_Cruncher\Exceptions\FilePointerInvalidException
     * @expectedExceptionMessage The filepointer is null on this object, use class::open to open a new filepointer
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
     * @expectedExceptionMessage The filepointer is null on this object, use class::open to open a new filepointer
     *
     * @return null
    **/
    public function writeInvalidFilePointerException()
    {
        $sourceFile = new CSVFile();
        $sourceFile->writeDataRow(['email' => 'test@test.com']);
    }

    /**
     * Test that get headers returns an array of headers
     * @test
    **/
    public function headerRetrievalTest()
    {
        $headers = $this->mockSourceCSV->getHeaders();
        $this->assertEquals(
            ['email', 'name', 'colour', 'dob', 'age'],
            $headers,
            'The headers read don\'t match those expected'
        );
    }

}
