<?php
namespace mfmbarber\DataCruncher\Tests\Integration\CSVTests;

use mfmbarber\DataCruncher\Config\Validation;
use mfmbarber\DataCruncher\Helpers\DataSource;
use mfmbarber\DataCruncher\Analysis\Statistics;
use mfmbarber\DataCruncher\Analysis\Config\Rule;
use mfmbarber\DataCruncher\Segmentation\Query;
use mfmbarber\DataCruncher\Segmentation\Merger;

use mfmbarber\DataCruncher\Exceptions\InvalidFileException;


class CSVErrorTest extends \PHPUnit_Framework_TestCase
{
    private $file;

    public function __construct()
    {
        $this->dir = getcwd() . '/tests/integration/CSVTests';
        $this->file = "{$this->dir}/InputFiles/1000row6columndata.csv";
    }

    public function setUp()
    {
        $this->sourceCSV = DataSource::generate('file', 'csv');
        $this->sourceCSV->setSource($this->file, ['modifier' => 'r']);
        $this->sourceCSV->sort('id');
    }

    public function tearDown()
    {
        Validation::deleteFiles("{$this->dir}/OutputFiles", ['md']);
    }

    /**
     * @expectedException        mfmbarber\DataCruncher\Exceptions\InvalidFileException
     * @expectedExceptionMessage File doesn't exist
    **/
    public function testItShouldFailIfFileDoesntExist()
    {
        $csv = DataSource::generate('file', 'csv');
        $csv->setSource(
            'FakeFile.csv',
            ['modifier' => 'r']
        );
    }

    /**
     * @expectedException        mfmbarber\DataCruncher\Exceptions\InvalidFileException
     * @expectedExceptionMessage The file provided is not in the correct format
    **/
    public function testItShouldFailIfFileIsEmpty()
    {
        $csv = DataSource::generate('file', 'csv');
        $csv->setSource(
            "{$this->dir}/InputFiles/Empty.csv",
            ['modifier' => 'r']
        );

    }

    /**
     * @expectException
     * @expectedExceptionMessage
    **/
    // public function testItShouldFailWritingToNonEmptyFile()
    // {
    //     $csv = DataSource::generate('file', 'csv');
    //     $csv->setSource(
    //         "{$this->dir}/InputFiles/NonEmpty.csv",
    //         ['modifier' => 'w']
    //     );
    // }

    /**
     * @expectedException           mfmbarber\DataCruncher\Exceptions\InvalidFileException
     * @expectedExceptionMessage    File is not set to read mode
    **/
    public function testItShouldFailReadingIfSetToWrite()
    {
        $csv = DataSource::generate('file', 'csv');
        $csv->setSource(
            "{$this->dir}/InputFiles/NonEmpty.csv",
            ['modifier' => 'w']
        );
        $csv->getNextDataRow()->current();

    }

    /**
     * @expectedException           mfmbarber\DataCruncher\Exceptions\InvalidFileException
     * @expectedExceptionMessage    The file provided is not in the correct format
    **/
    public function testItShouldFailGivenANonCSVFile()
    {
        $csv = DataSource::generate('file', 'csv');
        $csv->setSource(
            "{$this->dir}/InputFiles/NonCSV.csv",
            ['modifier' => 'r']
        );

    }

    /**
     * @expectedException           \Exception
     * @expectedExceptionMessage    One or more of fandangle is not in id, first_name, last_name, email, gender, ip_address
    **/
    public function testItShouldFailQueryingAHeaderThatDoesntExist()
    {
        $query = new Query();
        $result = $query->fromSource($this->sourceCSV)
            ->select(['fandangle'])
            ->where('email')
            ->condition('CONTAINS')
            ->value('stumbleupon')
            ->execute();
    }

    /**
     * @expectedException           mfmbarber\DataCruncher\Exceptions\InvalidFileException
     * @expectedExceptionMessage    File is not writable
    **/
    public function testItShouldFailIfNoWritePrivilages()
    {
        $csv = DataSource::generate('file', 'csv');
        $csv->setSource(
            "{$this->dir}/InputFiles/EmptyWriteProtected.csv",
            ['modifier' => 'w']
        );
    }

}
