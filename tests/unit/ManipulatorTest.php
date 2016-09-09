<?php
namespace mfmbarber\Data_Cruncher\Tests\Unit;

use mfmbarber\Data_Cruncher\Manipulator as Manipulator;

use mfmbarber\Data_Cruncher\Analysis\Statistics as Statistics;
use mfmbarber\Data_Cruncher\Segmentation\Query as Query;
use mfmbarber\Data_Cruncher\Helpers\Files\CSVFile as CSVFile;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class ManipulatorTest extends \PHPUnit_Framework_TestCase
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
    }

    public function tearDown()
    {
        $this->root = null;
    }

    /**
     * @test
    **/
    public function manipulatorSetDataSource()
    {
        $manipulator = new Manipulator(new CSVFile(), new Query(), new Statistics());
        $manipulator->setDataSource('vfs://home/test', []);
        $this->assertEquals($manipulator->getDataSourceLocation(), 'vfs://home/test');
    }

    /**
     * @test
    **/
    public function statisticsMethodReturnsStatisticsObject()
    {
        $manipulator = new Manipulator(new CSVFile(), new Query(), new Statistics());
        $manipulator->setDataSource('vfs://home/test', []);
        $this->assertEquals(get_class($manipulator->statistics()), 'mfmbarber\Data_Cruncher\analysis\Statistics');
    }

    /**
     * @test
    **/
    public function queryMethodReturnsStatisticsObject()
    {
        $manipulator = new Manipulator(new CSVFile(), new Query(), new Statistics());
        $manipulator->setDataSource('vfs://home/test', []);
        $this->assertEquals(get_class($manipulator->query()), 'mfmbarber\Data_Cruncher\Segmentation\Query');
    }

    /**
     * @test
     * @expectedException mfmbarber\Data_Cruncher\Exceptions\AttributeNotSetException
     * @expectedMessage Query object not passed during instantiation
    **/
    public function queryMethodThrowsAttributeNotSet()
    {
        $manipulator = new Manipulator(new CSVFile(), null, new Statistics());
        $manipulator->setDataSource('vfs://home/test', []);
        $manipulator->query();
    }

        /**
     * @test
     * @expectedException mfmbarber\Data_Cruncher\Exceptions\AttributeNotSetException
     * @expectedMessage Query Statistics object not passed during instantiation
    **/
    public function statisticsMethodThrowsAttributeNotSet()
    {
        $manipulator = new Manipulator(new CSVFile(), new Query);
        $manipulator->setDataSource('vfs://home/test', []);
        $manipulator->statistics();
    }
    
}