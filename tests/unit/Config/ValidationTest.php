<?php
namespace mfmbarber\DataCruncher\Tests\Unit\Config;

use mfmbarber\DataCruncher\Config\Validation as Validation;

class ValidationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test the validation function isNormalArray
     *
     * @return null
    **/
    public function testItShouldReturnTrueForNormalArray()
    {
        $this->assertTrue(Validation::isNormalArray(['test']));
    }

    /**
     * Test the validation function isNormal Array
     *
     * @return null
    **/
    public function testItShouldReturnFalseForNormalArray()
    {
        $this->assertFalse(Validation::isNormalArray(['a' => 'b']));
    }

    /**
     * Test the validation function isNormal Array
     *
     * @return null
    **/
    public function testItShouldReturnFalseForNormalArrayWrongSize()
    {
        $this->assertFalse(Validation::isNormalArray(['a', 'b'], 3));
    }

    /**
     * Test the validation function Associative Array
     *
     * @return null
    **/
    public function testItShouldReturnTrueForAssocArray()
    {
        $this->assertTrue(Validation::isAssociativeArray(['a' => 'b']));
    }

    /**
     * Test the validation function Associative Array
     *
     * @return null
    **/
    public function testItShouldReturnFalseAssocArray()
    {
        $this->assertFalse(Validation::isAssociativeArray(['a', 'b']));
    }

    /**
     * Test the validation function get date time object
     *
     * @return null
    **/
    public function testItShouldCreateValidDateTimeObject()
    {
        $date = \DateTime::createFromFormat('d/m/Y', '24/11/1987');
        $this->assertEquals($date, Validation::getDateTime('24/11/1987', 'd/m/Y'));
    }

    /**
     * Test the validation function get date time object
     *
     * @return null
    **/
    public function testItShouldReturnFalseForInvalidDateParams()
    {
        $this->assertFalse(Validation::getDateTime('a', 'Y'));
    }
}
