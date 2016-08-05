<?php
/**
 * XML File Tests
 *
 * @package Data_Cruncher
 * @subpackage tests/unit/Helpers
 * @author matt barber <mfmbarber@gmail.com>
 *
 */

namespace mfmbarber\Data_Cruncher\Tests\Unit\Helpers;

use mfmbarber\Data_Cruncher\Tests\Mocks as Mocks;
use mfmbarber\Data_Cruncher\Helpers\XMLFile as XMLFile;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class XMLFileTest extends \PHPUnit_Framework_TestCase
{

    private $root;

    private $mockSourceXML;
    private $mockOutXML;

    public function setUp()
    {
        $this->root = vfsStream::setup('home', 0777);
        $file = vfsStream::url('home/test', 0777);
        file_put_contents(
            $file,
            "<?xml version='1.0'?>
            <breakfast_menu>
                <food>
                    <name>Belgian Waffles</name>
                    <price>$5.95</price>
                    <description>
                        Two of our famous Belgian Waffles with plenty of real maple syrup
                    </description>
                    <calories>650</calories>
                </food>
                <food>
                    <name>Strawberry Belgian Waffles</name>
                    <price>$7.95</price>
                    <description>
                        Light Belgian waffles covered with strawberries and whipped cream
                    </description>
                    <calories>900</calories>
                </food>
                <food>
                    <name>Berry-Berry Belgian Waffles</name>
                    <price>$8.95</price>
                    <description>
                        Light Belgian waffles covered with an assortment of fresh berries and whipped cream
                    </description>
                    <calories>900</calories>
                </food>
                <food>
                    <name>French Toast</name>
                    <price>$4.50</price>
                    <description>
                        Thick slices made from our homemade sourdough bread
                    </description>
                    <calories>600</calories>
                </food>
                <food>
                    <name>Homestyle Breakfast</name>
                     <price>$6.95</price>
                    <description>
                        Two eggs, bacon or sausage, toast, and our ever-popular hash browns
                    </description>
                    <calories>950</calories>
                </food>
            </breakfast_menu>"
        );
        vfsStream::url('/home/test_out', 0777);
        $this->mockSourceXML = new XMLFile();
        $this->mockOutXML = new XMLFile();
        $this->mockSourceXML->setSource('vfs://home/test', ['modifier' => 'r']);
        $this->mockOutXML->setSource('vfs://home/test_out', ['modifier' => 'w']);
    }

    public function tearDown()
    {
        $this->mockSourceXML = null;
        $this->mockOutXML = null;
    }

    /**
     * @test
     *
     * @return void
    **/
    public function getNextDataRowWorksCorrectly()
    {
        $this->mockSourceXML->open(true, 'food', 'breakfast_menu');
        $this->assertEquals(
            $this->mockSourceXML->getNextDataRow(),
            [
               'name' => 'Belgian Waffles',
               'price' => '$5.95',
               'description' => 'Two of our famous Belgian Waffles with plenty of real maple syrup',
               'calories' => '650'
            ],
            "Get next data row did not return the correct assoc_array"
        );
    }
    /**
     * Unit test, If a file pointer hasn't been opened and a read is attempted
     *
     * @test
     * @expectedException        mfmbarber\Data_Cruncher\Exceptions\FilePointerInvalidException
     * @expectedExceptionMessage The filepointer is null on this object, use class::open to open a new filepointer
     *
     * @return null
     */
    public function readBeforeOpenThrowsException()
    {
        $this->mockOutXML->writeDataRow([]);
    }
    /**
     * @test
     */
    public function writeDataRowWorksCorrectly()
    {
        $this->mockOutXML->open(false, 'test', 'data');
        $this->mockOutXML->writeDataRow(
            [
                'name' => 'tony',
                'hero' => 'iron man'
            ]
        );
        $this->mockOutXML->close();
        $this->mockOutXML->open(true, 'test', 'data');
        $this->assertEquals(
            $this->mockOutXML->getNextDataRow(),
            [
                'name' => 'tony',
                'hero' => 'iron man'
            ],
            "writeDataRow didn't write back the correct data"
        );
    }

    /**
     * @test
     */
    public function writeEmptyDataRowReturnsFalse()
    {
        $this->mockOutXML->open(false, 'test', 'data');
        $this->assertFalse(
            $this->mockOutXML->writeDataRow([])
        );
    }
    /**
     * Unit test, If a file pointer hasn't been opened and a write is attempted
     *
     * @test
     * @expectedException        mfmbarber\Data_Cruncher\Exceptions\FilePointerInvalidException
     * @expectedExceptionMessage The filepointer is null on this object, use class::open to open a new filepointer
     *
     * @return null
     */
    public function writeBeforeOpenThrowsException()
    {
        $this->mockOutXML->writeDataRow([]);
    }
}
