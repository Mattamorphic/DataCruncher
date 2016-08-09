<?php
namespace mfmbarber\Data_Cruncher\Tests\Unit\Segmentation;
use mfmbarber\Data_Cruncher\Segmentation\Split as Split;
use mfmbarber\Data_Cruncher\Segmentation\Query as Query;

use org\bovigo\vfs\vfsStream,
    org\bovigo\vfs\vfsStreamDirectory;

class SplitTest extends \PHPUnit_Framework_TestCase
{
    private $root;

    private $mockSourceCSV;
    private $mockOutCSV;

    private function _generateMockFile($class_name)
    {
        $file = $this->getMockBuilder($class_name)
        ->setMethods(['fileExists', 'readable', 'writable'])
        ->getMock();
        $file->method('readable')->willReturn(true);
        $file->method('writable')->willReturn(true);
        $file->method('fileExists')->willReturn(true);
        return $file;
    }

    public function setUp() {
        $this->root = vfsStream::setup('home', 0777);
        $file = vfsStream::url('home/test', 0777);
        file_put_contents($file,
            "email, name, colour, dob, age\n"
            ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
            ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
            ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
            ."no_name@something.com, , \"green\", 01/01/2000, fifteen"
        );
        vfsStream::url('home/test_out', 0777);
        $this->mockSourceCSV = $this->_generateMockFile('mfmbarber\Data_Cruncher\Helpers\CSVFile');
        $this->mockSourceCSV->setSource('vfs://home/test', ['modifier' => 'r']);
        $this->mockOutCSV = $this->_generateMockFile('mfmbarber\Data_Cruncher\Helpers\CSVFile');
        $this->mockOutCSV->setSource('vfs://home/test_out', ['modifier' => 'r']);

    }

    public function tearDown() {
        $this->mockSourceCSV = null;
        $this->mockOutCSV = null;
    }
    /**
     * @test
    **/
    public function executeSplitWorksCorrectlyHorizontal()
    {
        $split = new Split();

        $result = $split->fromSource($this->mockSourceCSV)
            ->horizontal(2)
            ->execute();

        $expected = [
            [
                [
                    'email' => 'mfmbarber@test.com',
                    'name' => 'matt',
                    'colour' => 'black, green, blue',
                    'dob' => '24/11/1987',
                    'age' => 28
                ],
                [
                    'email' => 'matt.barber@test.com',
                    'name' => 'matthew',
                    'colour' => 'red, green',
                    'dob' => '01/12/1980',
                    'age' => 35
                ]
            ],
            [
                [
                    'email' => 'tony.stark@avengers.com',
                    'name' => 'tony',
                    'colour' => 'red, gold',
                    'dob' => '02/05/1990',
                    'age' => 25
                ],
                [
                    'email' => 'no_name@something.com',
                    'name' => '',
                    'colour' => 'green',
                    'dob' => '01/01/2000',
                    'age' => 'fifteen'
                ],
            ]
        ];
        $this->assertEquals(
            $result,
            $expected,
            'Execute didn\'t split the data as expected'
        );
    }
    /**
     * @test
    **/
    public function executeSplitWorksCorrectlyVertical()
    {
        $split = new Split();
        $result = $split->fromSource($this->mockSourceCSV)
            ->vertical(['email, name', 'email, age'])
            ->execute();
        $expected = [
            [
                [
                    'email' => 'mfmbarber@test.com',
                    'name' => 'matt'
                ],
                [
                    'email' => 'matt.barber@test.com',
                    'name' => 'matthew'
                ],
                [
                    'email' => 'tony.stark@avengers.com',
                    'name' => 'tony'
                ],
                [
                    'email' => 'no_name@something.com',
                    'name' => ''
                ]
            ],
            [
                [
                    'email' => 'mfmbarber@test.com',
                    'age' => '28'
                ],
                [
                    'email' => 'matt.barber@test.com',
                    'age' => '35'
                ],
                [
                    'email' => 'tony.stark@avengers.com',
                    'age' => '25'
                ],
                [
                    'email' => 'no_name@something.com',
                    'age' => 'fifteen'
                ]
            ],

        ];
        $this->assertEquals(
            $result,
            $expected,
            'The vertical split wasn\'t correct'
        );
    }
}
