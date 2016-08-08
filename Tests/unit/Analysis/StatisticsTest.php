<?php
namespace mfmbarber\Data_Cruncher\Tests\Unit\Analysis;

use mfmbarber\Data_Cruncher\Analysis\Statistics as Statistics;
use mfmbarber\Data_Cruncher\Helpers\CSVFile as CSVFile;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class StatisticsTest extends \PHPUnit_Framework_TestCase
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
            "email, name, colour, dob, age\n"
            ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
            ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
            ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
            ."j@something.com, john, \"green\", 01/01/2000, 15"
        );
        vfsStream::url('home/test_out', 0777);
        $this->mockSourceCSV = new CSVFile;
        $this->mockOutCSV = new CSVFile;

        $this->mockSourceCSV->setSource('vfs://home/test', ['modifier' => 'r']);
        $this->mockOutCSV->setSource('vfs://home/test_out', ['modifier' => 'w']);
    }

    public function tearDown()
    {
        $this->mockSourceCSV = null;
        $this->mockOutCSV = null;
    }
    /**
     * Tests the execution of percentages grouping exactly by the values in a
     * field
     *
     * @test
     *
     * @return null
    **/
    public function executePercentagesWorkCorrectlyExactGrouping()
    {
        $stats = new Statistics();
        $result = $stats->fromSource($this->mockSourceCSV)
            ->percentages()
            ->setField('name')
            ->groupExact()
            ->execute();
        $this->assertEquals(
            [
                'matt' => 25,
                'matthew' => 25,
                'tony' => 25,
                'john' => 25
            ],
            $result,
            "Execute did not return the expected results"
        );
    }
    /**
     * Tests the execution of percentages grouping the values in a field
     * by numerical range i.e. 10 would be (1 -> 10, 10 -> 20)
     *
     * @test
     *
     * @return null
    **/
    public function executePercentagesWorkCorrectlyNumericGrouping()
    {
        $stats = new Statistics();
        $result = $stats->fromSource($this->mockSourceCSV)
            ->percentages()
            ->setField('age')
            ->groupNumeric(10)
            ->execute();
        $this->assertEquals(
            [
                '10, 20' => 25,
                '20, 30' => 50,
                '30, 40' => 25
            ],
            $result,
            "Execute did not return the expected results"
        );
    }
    /**
     * Tests the execution of percentages grouping the values in a field
     * by date
     *
     * @test
     *
     * @return null
    **/
    public function executePercentagesWorkCorrectlyDateGrouping()
    {
        $stats = new Statistics();
        $result = $stats->fromSource($this->mockSourceCSV)
            ->percentages()
            ->setField('dob')
            ->groupDate('d/m/Y', 'Y')
            ->execute();
        $this->assertEquals(
            [
                '1987' => 25,
                '1980' => 25,
                '1990' => 25,
                '2000' => 25
            ],
            $result,
            "Execute did not return the expected results"
        );
    }

}
