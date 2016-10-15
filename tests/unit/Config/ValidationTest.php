<?php 
namespace mfmbarber\DataCruncher\Tests\Unit\Config;

use mfmbarber\DataCruncher\Config\Validation as Validation;

class ValidationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
    **/
    public function isNormalArrayReturnsTrue()
    {
        $this->assertTrue(Validation::isNormalArray(['test']));
    }

    /**
     * @test
    **/
    public function isNormalArrayReturnsFalse()
    {
        $this->assertFalse(Validation::isNormalArray(['a' => 'b']));
    }
    
    /**
     * @test
    **/
    public function isNormalArrayReturnsFalseGivenSize()
    {
        $this->assertFalse(Validation::isNormalArray(['a', 'b'], 3));
    }

    /**
     * @test
    **/
    public function isAssociativeArrayReturnsTrue()
    {
        $this->assertTrue(Validation::isAssociativeArray(['a' => 'b']));
    }

    /**
     * @test
    **/
    public function isAssociativeArrayReturnsFalse()
    {
        $this->assertFalse(Validation::isAssociativeArray(['a', 'b']));
    }

    /**
     * @test
    **/
    public function getDateTimeReturnsDateObj()
    {
        $date = \DateTime::createFromFormat('d/m/Y', '24/11/1987');
        $this->assertEquals($date, Validation::getDateTime('24/11/1987', 'd/m/Y'));
    }

    /**
     * @test
    **/
    public function getDateTimeReturnsFalse()
    {
        $this->assertFalse(Validation::getDateTime('a', 'Y'));
    }
}
