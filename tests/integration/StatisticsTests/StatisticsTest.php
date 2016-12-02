<?php
namespace mfmbarber\DataCruncher\Tests\Integration\StatisticsTests;

use mfmbarber\DataCruncher\Config\Validation;
use mfmbarber\DataCruncher\Helpers\DataSource;
use mfmbarber\DataCruncher\Analysis\Statistics;
use mfmbarber\DataCruncher\Analysis\Config\Rule;


class StatisticTest extends \PHPUnit_Framework_TestCase
{
    private $dir;

    public function setUp()
    {
        $this->dir = getcwd() . '/tests/integration/';
    }
    public function tearDown()
    {
        Validation::deleteFiles($this->dir.'StatisticsTests/OutputFiles', ['md']);
    }

    public function testItShouldAnalyseACSVFile()
    {
        $statistics = new Statistics();
        $rule = new Rule();
        $rule = $rule->setField('gender')->groupExact();
        $statistics->addRule($rule);
        $csv = DataSource::generate('file', 'csv');
        $file = $this->dir . 'CSVTests/InputFiles/1000row6columndata.csv';
        $csv->setSource($file, ['modifier' => 'r']);
        $result = $statistics->fromSource($csv)
            ->percentages()
            ->execute();
        $this->assertEquals(
            array_pop($result),
            [
                'Male' => 53.400000000000006,
                'Female' => 46.600000000000001
            ]
        );
    }

    public function testItShouldAnalyseAXMLFile()
    {
        $statistics = new Statistics();
        $rule = new Rule();
        $rule = $rule->setField('gender')->groupExact();
        $statistics->addRule($rule);
        $xml = DataSource::generate('file', 'xml', 'record', 'dataset');
        $file = $this->dir . 'XMLTests/InputFiles/1000row6fielddata.xml';
        $xml->setSource($file, ['modifier' => 'r']);
        $result = $statistics->fromSource($xml)
            ->percentages()
            ->execute();
        $this->assertEquals(
            array_pop($result),
            [
                'Male' => 50.200000000000003,
                'Female' => 49.800000000000004
            ]
        );
    }
}
