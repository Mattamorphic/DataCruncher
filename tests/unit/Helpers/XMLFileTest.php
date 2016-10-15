<?php
/**
 * XML File Tests
 *
 * @package DataCruncher
 * @subpackage tests/unit/Helpers
 * @author matt barber <mfmbarber@gmail.com>
 *
 */

namespace mfmbarber\DataCruncher\Tests\Unit\Helpers;

use mfmbarber\DataCruncher\Tests\Mocks as Mocks;
use mfmbarber\DataCruncher\Helpers\Files\XMLFile as XMLFile;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class XMLFileTest extends \PHPUnit_Framework_TestCase
{

    private $root;

    private $mockSourceXML;
    private $mockOutXML;

    /**
     * Setup creates a virtual file system before each test with a dummy file 
     */
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
        $this->mockSourceXML = new XMLFile('food', 'breakfast_menu');
        $this->mockOutXML = new XMLFile('food', 'breakfast_menu');
        $this->mockSourceXML->setSource('vfs://home/test', ['modifier' => 'r']);
        $this->mockOutXML->setSource('vfs://home/test_out', ['modifier' => 'w']);
    }

    /**
     * After each test clear down our vfs
     */
    public function tearDown()
    {
        $this->root = null;
        $this->mockSourceXML = null;
        $this->mockOutXML = null;
    }

    /**
     * It should return an array of headers and values 
     *
     * @test
     *
     * @return void
    **/
    public function getNextDataRow()
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
     * @expectedException        mfmbarber\DataCruncher\Exceptions\FilePointerInvalidException
     * @expectedExceptionMessage The filepointer is null on this object, use class::open to open a new filepointer
     *
     * @return null
     */
    public function readBeforeOpenThrowsException()
    {
        $this->mockOutXML->writeDataRow([]);
    }
    /**
     * It should write the correct data back to the file
     *
     * @test
     *
     * @return void
     */
    public function writeDataRow()
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
     * @expectedException        mfmbarber\DataCruncher\Exceptions\FilePointerInvalidException
     * @expectedExceptionMessage The filepointer is null on this object, use class::open to open a new filepointer
     *
     * @return null
     */
    public function writeBeforeOpenThrowsException()
    {
        $this->mockOutXML->writeDataRow([]);
    }
    /**
     * Unit test, If a file pointer has been opened and another open is called
     *
     * @test
     * @expectedException        mfmbarber\DataCruncher\Exceptions\FilePointerExistsException
     * @expectedExceptionMessage A filepointer exists on this object, use class::close to close the current pointer
     *
     * @return null
     */
    public function openWhileFilePointerIsOpenThrowsException()
    {
        // initial open
        $this->mockSourceXML->open(true, 'food', 'breakfast_menu');
        // subsequent open
        $this->mockSourceXML->open(true, 'food', 'breakfast_menu');
    }

    /**
     * @test
     */
    public function resetTheFilePointerToStartOfFile()
    {
        $this->mockSourceXML->open(true, 'food', 'breakfast_menu');
        $this->mockSourceXML->getNextDataRow();
        $this->mockSourceXML->getNextDataRow();
        $this->mockSourceXML->reset();
        $this->assertEquals(
            $this->mockSourceXML->getNextDataRow(),
            [
               'name' => 'Belgian Waffles',
               'price' => '$5.95',
               'description' => 'Two of our famous Belgian Waffles with plenty of real maple syrup',
               'calories' => '650'
            ]
        );
    }

    /**
     * @test
     * @expectedException        mfmbarber\DataCruncher\Exceptions\FilePointerInvalidException
     * @expectedExceptionMessage The filepointer is null on this object, use class::open to open a new filepointer
     */
    public function resetFilePointerOnNullFileThrowsException()
    {
        $this->mockSourceXML->reset();
    }

    /**
     * @test
     * @expectedException        mfmbarber\DataCruncher\Exceptions\FilePointerInvalidException
     * @expectedExceptionMessage The filepointer is null on this object, use class::open to open a new filepointer
     */
    public function closeFilePointerOnNullFileThrowsException()
    {
        $this->mockSourceXML->close();
    }
}
