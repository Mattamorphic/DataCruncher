<?php
namespace mfmbarber\DataCruncher\Tests\Unit\Analysis;

use mfmbarber\DataCruncher\Analysis\Find as Find;

use mfmbarber\DataCruncher\Analysis\Config\Rule as Rule;
use mfmbarber\DataCruncher\Helpers\Files\CSVFile as CSVFile;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class FindTest extends \PHPUnit_Framework_TestCase
{
    private $root;

    private $mockSourceCSV;
    private $mockOutCSV;

    public function setUp()
    {
        $this->root = vfsStream::setup('home', 0777);
        $file = vfsStream::url('home/test', 0777);
        file_put_contents(
            $file,
            "email, name, colour, dob, age, phone\n"
            ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28, apple iphone 6\n"
            ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35, samsung galaxy s6\n"
            ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25, samsung note 7\n"
            ."j@something.com, john, \"green\", 01/01/1965, 32, htc m8"
        );
        vfsStream::url('home/test_out', 0777);
        $this->mockSourceCSV = new CSVFile;
        $this->mockOutCSV = new CSVFile;

        $this->mockSourceCSV->setSource('vfs://home/test', ['modifier' => 'r']);
        $this->mockOutCSV->setSource('vfs://home/test_out', ['modifier' => 'w']);
    }

    public function tearDown()
    {
        $this->root = null;
        $this->mockSourceCSV = null;
        $this->mockOutCSV = null;
    }

    public function testItShouldReturnTheRowWithMaxValue()
    {
        $find = new Find();
        $result = $find->from($this->mockSourceCSV)
            ->max('age')
            ->execute();
        $this->assertEquals(
            [
                'max' => [
                    'email' => 'matt.barber@test.com',
                    'name' => 'matthew',
                    'colour' => 'red, green',
                    'dob' => '01/12/1980',
                    'age' => '35',
                    'phone' => 'samsung galaxy s6'
                ]
            ],
            $result
        );

    }

    public function testItShouldReturnTheRowWithMinValue()
    {
        $find = new Find();
        $result = $find->from($this->mockSourceCSV)
            ->min('age')
            ->execute();
        $this->assertEquals(
            [
                'min' => [
                    'email' => 'tony.stark@avengers.com',
                    'name' => 'tony',
                    'colour' => 'red, gold',
                    'dob' => '02/05/1990',
                    'age' => '25',
                    'phone' => 'samsung note 7'
                ]
            ],
            $result
        );
    }

    public function testItShouldReturnTheRowWithMinAndMaxValue()
    {
        $find = new Find();
        $result = $find->from($this->mockSourceCSV)
            ->max('age')
            ->min('age')
            ->execute();
        $this->assertEquals(
            [
                'min' => [
                    'email' => 'tony.stark@avengers.com',
                    'name' => 'tony',
                    'colour' => 'red, gold',
                    'dob' => '02/05/1990',
                    'age' => '25',
                    'phone' => 'samsung note 7'
                ],
                'max' => [
                    'email' => 'matt.barber@test.com',
                    'name' => 'matthew',
                    'colour' => 'red, green',
                    'dob' => '01/12/1980',
                    'age' => '35',
                    'phone' => 'samsung galaxy s6'
                ]
            ],
            $result
        );
    }

    public function testItShouldReturnTheDeviation()
    {
        $find = new Find();
        $result = $find->from($this->mockSourceCSV)
            ->deviation('age', 1, ['email'])
            ->execute();
        $this->assertEquals(
            [
                'deviations' => [
                    ['email' => 'mfmbarber@test.com', 'age_deviation' => -2],
                    ['email' => 'matt.barber@test.com', 'age_deviation' => 5],
                    ['email' => 'tony.stark@avengers.com', 'age_deviation' => -5],
                    ['email' => 'j@something.com', 'age_deviation' => 2]
                ]
            ],
            $result
        );
    }

    public function testItShouldReturnTheDeviationAndTime()
    {
        $find = new Find();
        $result = $find->from($this->mockSourceCSV)
            ->deviation('age', 1, ['email'])
            ->timer()
            ->execute();
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('timer', $result);
        $this->assertTrue(is_integer($result['timer']['elapsed']));
    }
}
