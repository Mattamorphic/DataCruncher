<?php
namespace mfmbarber\DataCruncher\Tests\Integration\QueryTests;

use mfmbarber\DataCruncher\Config\Validation;
use mfmbarber\DataCruncher\Helpers\DataSource;
use mfmbarber\DataCruncher\Processor;


class QueryTest extends \PHPUnit_Framework_TestCase
{
    private $dir;

    public function setUp()
    {
        $this->dir = getcwd() . '/tests/integration/';
    }
    public function tearDown()
    {
        Validation::deleteFiles($this->dir.'QueryTests/OutputFiles', ['md']);
    }

    public function testItShouldQueryACSVFile()
    {
        $query = Processor::generate('segmentation', 'query');
        $csv = DataSource::generate('file', 'csv');
        $file = $this->dir . 'CSVTests/InputFiles/1000row6columndata.csv';
        $csv->setSource($file, ['modifier' => 'r']);
        $result = $query->from($csv)
            ->select(['id', 'email'])
            ->where('ip_address')
            ->condition('CONTAINS')
            ->value('140.11.')
            ->execute();
        $this->assertEquals(
            $result,
            [
                [
                    'id' => 4,
                    'email' => 'asimmons3@infoseek.co.jp'
                ]
            ]

        );
    }

    public function testItShouldQueryAXMLFile()
    {
        $query = Processor::generate('segmentation', 'query');
        $xml = DataSource::generate('file', 'xml', 'record', 'dataset');
        $file = $this->dir . 'XMLTests/InputFiles/1000row6fielddata.xml';
        $xml->setSource($file, ['modifier' => 'r']);
        $result = $query->from($xml)
            ->select(['id', 'email'])
            ->where('ip_address')
            ->condition('CONTAINS')
            ->value('106.209.')
            ->execute();
        $this->assertEquals(
            $result,
            [
                [
                    'id' => 4,
                    'email' => 'asimpson3@techcrunch.com'
                ]
            ]

        );
    }


    public function testItShouldOutputCSVString()
    {
        $query = Processor::generate('segmentation', 'query');
        $xml = DataSource::generate('file', 'xml', 'record', 'dataset');
        $file = $this->dir . 'XMLTests/InputFiles/1000row6fielddata.xml';
        $xml->setSource($file, ['modifier' => 'r']);
        $system = DataSource::generate('system', 'csv');
        $result = $query->from($xml)
            ->select(['id', 'email'])
            ->where('ip_address')
            ->condition('CONTAINS')
            ->value('106.209.')
            ->out($system)
            ->execute();
        $this->assertEquals(
            $result['data'],
            "id,email\n".
            "4,asimpson3@techcrunch.com\n"

        );
    }

    public function testItShouldQueryADatabaseTable(){}

    public function testItShouldOutputToCSV()
    {
        $query = Processor::generate('segmentation', 'query');
        $xml = DataSource::generate('file', 'xml', 'record', 'dataset');
        $csv = DataSource::generate('file', 'csv');
        $file = $this->dir . 'XMLTests/InputFiles/1000row6fielddata.xml';
        $o_file = $this->dir . 'QueryTests/OutputFiles/id990to1000.xml';
        $xml->setSource($file, ['modifier' => 'r']);
        $csv->setSource($o_file, ['modifier' => 'w']);
        $result = $query->from($xml)
            ->select(['email'])
            ->where('id')
            ->condition('GREATER')
            ->value(999)
            ->out($csv)
            ->execute();
        $this->assertEquals($result['data'], 1);
        $this->assertEquals(
            file_get_contents($o_file),
            "email\n".
            "jboydrr@unesco.org\n"
        );
    }
    public function testItShouldOutputToXML(){
        $query = Processor::generate('segmentation', 'query');
        $xml = DataSource::generate('file', 'xml', 'record', 'dataset');
        $csv = DataSource::generate('file', 'csv');
        $file = $this->dir . 'CSVTests/InputFiles/1000row6columndata.csv';
        $o_file = $this->dir . 'QueryTests/OutputFiles/id999to1000.xml';
        $csv->setSource($file, ['modifier' => 'r']);
        $xml->setSource($o_file, ['modifier' => 'w']);
        $result = $query->from($csv)
            ->select(['email'])
            ->where('id')
            ->condition('GREATER')
            ->value(999)
            ->out($xml)
            ->execute();
        $this->assertEquals($result['data'], 1);
        $this->assertEquals(
            file_get_contents($o_file),
            "<?xml version=\"1.0\"?>\n".
            "<dataset><record><email>fmillsrr@acquirethisname.com</email></record></dataset>\n"
        );

    }
    public function testItShouldOutputToDBTable(){}
    public function testItShouldQueryEquals()
    {
        $query = Processor::generate('segmentation', 'query');
        $xml = DataSource::generate('file', 'xml', 'record', 'dataset');
        $xml->setSource($this->dir . 'XMLTests/InputFiles/1000row6fielddata.xml');
        $result = $query->from($xml)
            ->select(['email'])
            ->where('id')
            ->condition('EQUALS')
            ->value(600)
            ->execute();
        $this->assertEquals(
            $result,
            [
                [
                    'email' => 'egarzagn@nbcnews.com'
                ]
            ]
        );
    }
    public function testItShouldQueryDates(){}
}
