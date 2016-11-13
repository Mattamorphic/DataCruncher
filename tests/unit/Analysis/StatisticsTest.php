<?php
namespace mfmbarber\DataCruncher\Tests\Unit\Analysis;

use mfmbarber\DataCruncher\Analysis\Statistics as Statistics;

use mfmbarber\DataCruncher\Analysis\Config\Rule as Rule;
use mfmbarber\DataCruncher\Helpers\Files\CSVFile as CSVFile;

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
            "email, name, colour, dob, age, phone\n"
            ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28, apple iphone 6\n"
            ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35, samsung galaxy s6\n"
            ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25, samsung note 7\n"
            ."j@something.com, john, \"green\", 01/01/2000, 15, htc m8"
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
        $rule = new Rule();
        $rule = $rule->setField('name')->groupExact();
        $stats->addRule($rule);
        $result = $stats->fromSource($this->mockSourceCSV)
            ->percentages()
            ->execute();
        $result = array_pop($result);
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
         $rule = new Rule();
        $rule = $rule->setField('age')->groupNumeric(10);
        $stats->addRule($rule);
        $result = $stats->fromSource($this->mockSourceCSV)
            ->percentages()
            ->execute();
        $result = array_pop($result);
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
        $rule = new Rule();
        $rule = $rule->setField('dob') ->groupDate('d/m/Y', 'Y');
        $stats->addRule($rule);
        $result = $stats->fromSource($this->mockSourceCSV)
            ->percentages()
            ->execute();
        $result = array_pop($result);
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

    // TODO : Fix the outfile functionality...
    /**
     * @t/est
    **/
    public function executePercentageOutfile()
    {
        $stats = new Statistics();
        $rule = new Rule();
        $rule = $rule->setField('dob') ->groupDate('d/m/Y', 'Y');
        $stats->addRule($rule);
        $stats->fromSource($this->mockSourceCSV)
            ->percentages()
            ->execute($this->mockOutCSV);
        $this->assertEquals(
            file_get_contents($this->mockOutCSV->getSourceName()),
            "dob,PERCENT\n1987,25\n1980,25\n1990,25\n2000,25\n",
            file_get_contents($this->mockOutCSV->getSourceName())
        );
    }

    /**
     * @test
    **/
    public function executeRegexTest()
    {
        $stats = new Statistics();
        $rule = new Rule();
        $rule = $rule->setField('phone')->groupRegex('/^([\w\-]+)/i');
        $stats->addRule($rule);
        $result = $stats->fromSource($this->mockSourceCSV)
            ->percentages()
            ->execute();
        $result = array_pop($result);
        $this->assertEquals(
            [
                'apple' => 25,
                'samsung' => 50,
                'htc' => 25
            ],
            $result,
            "Execute did not return the expected results"
        );
    }

    /**
     * @test
    **/
    public function executeMultipleStats()
    {
        $stats = new Statistics();
        $rules = [];
        $rule = new Rule();
        $rule->setField('phone')->groupRegex('/^([\w\-]+)/i');
        $stats->addRule($rule);
        $rule->setField('colour')->groupRegex('/([^,]+)/');
        $stats->addRule($rule);
        $result = $stats->fromSource($this->mockSourceCSV)
            ->percentages()
            ->execute();
        $this->assertEquals(
            [
                [
                    'apple' => 25,
                    'samsung' => 50,
                    'htc' => 25
                ],
                [
                    'red' => 50,
                    'black' => 25,
                    'green' => 25
                ]
            ],
            $result,
            "Execute did not return the expected results"
        );
    }
    /**
     * @test
    **/
    public function executeStatisticsWithLabels()
    {
        $stats = new Statistics();
        $rules = [];
        $rule = new Rule();
        $rule->setField('phone')->groupRegex('/^([\w\-]+)/i')->setLabel('company');
        $stats->addRule($rule);
        $rule = new Rule();
        $rule->setField('colour')->groupRegex('/([^,]+)/')->setLabel('colour');
        $stats->addRule($rule);
        $result = $stats->fromSource($this->mockSourceCSV)
            ->percentages()
            ->execute();
        $this->assertEquals(
            [
                'company' => [
                    'apple' => 25,
                    'samsung' => 50,
                    'htc' => 25
                ],
                'colour' => [
                    'red' => 50,
                    'black' => 25,
                    'green' => 25
                ]
            ],
            $result,
            "Execute did not return the expected results"
        );
    }

    /**
    * @test
    **/
    public function trackTimeOfExecution()
    {
        $stats = new Statistics();
        $rules = [];
        $rule = new Rule();
        $rule->setField('phone')->groupRegex('/^([\w\-]+)/i');
        $stats->addRule($rule);
        $rule->setField('colour')->groupRegex('/([^,]+)/');
        $stats->addRule($rule);
        $result = $stats->fromSource($this->mockSourceCSV)
            ->percentages()
            ->execute(null, true);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('timer', $result);
        $this->assertTrue(is_integer($result['timer']['elapsed']));
    }
}
