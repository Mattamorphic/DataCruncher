<?php
namespace mfmbarber\Data_Cruncher\Tests\Unit\Analysis;
use mfmbarber\Data_Cruncher\Tests\Mocks as Mocks;
use mfmbarber\Data_Cruncher\Analysis\Statistics as Statistics;

class StatisticsTest extends \PHPUnit_Framework_TestCase
{
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
        $data = "email, name, colour, dob, age\n"
        ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
        ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
        ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
        ."thor@avengers.com, thor, \"red, silver\", 02/05/1790, 225";
        $customMocks = new Mocks();
        $sourceFile = $customMocks->createMockSourceFile($data);
        $stats = new Statistics();
        $result = $stats->fromSource($sourceFile)
            ->percentages()
            ->setField('name')
            ->groupExact()
            ->execute();
        $this->assertEquals(
            [
                'matt' => 25,
                'matthew' => 25,
                'tony' => 25,
                'thor' => 25
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
        $data = "email, name, colour, dob, age\n"
        ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
        ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
        ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
        ."thor@avengers.com, thor, \"red, silver\", 02/05/1790, 225";
        $customMocks = new Mocks();
        $sourceFile = $customMocks->createMockSourceFile($data);
        $stats = new Statistics();
        $result = $stats->fromSource($sourceFile)
            ->percentages()
            ->setField('age')
            ->groupNumeric(10)
            ->execute();
        $this->assertEquals(
            [
                '20, 30' => 50,
                '30, 40' => 25,
                '220, 230' => 25
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
        $data = "email, name, colour, dob, age\n"
        ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
        ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
        ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
        ."thor@avengers.com, thor, \"red, silver\", 02/05/1790, 225";
        $customMocks = new Mocks();
        $sourceFile = $customMocks->createMockSourceFile($data);
        $stats = new Statistics();
        $result = $stats->fromSource($sourceFile)
            ->percentages()
            ->setField('dob')
            ->groupDate('d/m/Y', 'Y')
            ->execute();
        $this->assertEquals(
            [
                '1987' => 25,
                '1980' => 25,
                '1990' => 25,
                '1790' => 25
            ],
            $result,
            "Execute did not return the expected results"
        );
    }

}
