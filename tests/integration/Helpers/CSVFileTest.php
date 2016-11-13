<?php
namespace mfmbarber\DataCruncher\Tests\Integration\Helpers;

use mfmbarber\DataCruncher\Helpers\Files\CSVFile as CSVFile;


class CSVFileTest extends \PHPUnit_Framework_TestCase
{
    private $file;

    public function __construct()
    {
      parent::__construct();
      $this->file = getcwd() . '/tests/integration/Helpers/test.csv';
    }

    public function setUp()
    {
        file_put_contents(
            $this->file,
            "email, name, colour, dob, age\n"
            ."mfmbarber@test.com, matt, \"black\", 24/11/1987, 28\n"
            ."matt.barber@test.com, matthew, \"red\", 01/12/1980, 35\n"
            ."tony.stark@avengers.com, tony, \"red\", 02/05/1990, 25\n"
        );
        $this->sourceCSV = new CSVFile();
        $this->sourceCSV->setSource($this->file, ['modifier' => 'r']);
    }

    public function tearDown()
    {
        unlink($this->file);
    }
    /**
     * Unit test, sorting a file
     *
     * @test
     *
     * @return null
    **/
    public function sortCSVFileNumeric()
    {
        $this->sourceCSV->sort('age');

        $this->sourceCSV->open();
        while ([] !== ($row = $this->sourceCSV->getNextDataRow())) {
            $result[] = $row;
        }
        $this->sourceCSV->close();
        $this->assertEquals(
            [
                [
                    'email' => 'tony.stark@avengers.com',
                    'name' => 'tony',
                    'colour' => 'red',
                    'dob' => '02/05/1990',
                    'age' => '25'
                ],
                [
                    'email' => 'mfmbarber@test.com',
                    'name' => 'matt',
                    'colour' => 'black',
                    'dob' => '24/11/1987',
                    'age' => '28'
                ],
                [
                    'email' => 'matt.barber@test.com',
                    'name' => 'matthew',
                    'colour' => 'red',
                    'dob' => '01/12/1980',
                    'age' => '35'
                ]
            ],
            $result
        );
    }
    /**
     * Unit test, sorting a file
     *
     * @test
     *
     * @return null
    **/
    public function sortCSVFileString()
    {
        $this->sourceCSV->sort('name');

        $this->sourceCSV->open();
        while ([] !== ($row = $this->sourceCSV->getNextDataRow())) {
            $result[] = $row;
        }
        $this->sourceCSV->close();
        $this->assertEquals(
            [
                [
                    'email' => 'mfmbarber@test.com',
                    'name' => 'matt',
                    'colour' => 'black',
                    'dob' => '24/11/1987',
                    'age' => '28'
                ],
                [
                    'email' => 'matt.barber@test.com',
                    'name' => 'matthew',
                    'colour' => 'red',
                    'dob' => '01/12/1980',
                    'age' => '35'
                ],
                [
                    'email' => 'tony.stark@avengers.com',
                    'name' => 'tony',
                    'colour' => 'red',
                    'dob' => '02/05/1990',
                    'age' => '25'
                ]
            ],
            $result
        );
    }
}
