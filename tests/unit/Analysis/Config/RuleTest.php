<?php
namespace mfmbarber\DataCruncher\Tests\Unit\Analysis\Config;

use mfmbarber\DataCruncher\Analysis\Config\Rule as Rule;
use mfmbarber\DataCruncher\Helpers\Files\CSVFile as CSVFile;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class RuleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test setting the field for the rule
     *
     * @return null
    **/
    public function testItShouldSetTheFieldToTest()
    {
        $rule = new Rule();
        $rule->setField('test');
        $result = $rule->get();
        $this->assertEquals(
            $result->field,
            'test'
        );
    }

    /**
     * Test settinwg the label for the rule
     *
     * @return null
    **/
    public function testItShouldSetTheLabelForTheTest()
    {
        $rule = new Rule();
        $rule->setLabel('test');
        $result = $rule->get();
        $this->assertEquals(
            $result->label,
            'test'
        );
    }

    /**
     * Test grouping a rule by exact values
     *
     * @return null
    **/
    public function testItShouldGroupByExact()
    {
        $rule = new Rule();
        $rule->groupExact();
        $result = $rule->get();
        $func = $result->func;
        $this->assertEquals(
            $func('a', null),
            'a'
        );
    }

    /**
     * Test grouping a rule by numeric boundaries
     *
     * @return null
    **/
    public function testItShouldGroupByNumeric()
    {
        $rule = new Rule();
        $rule->groupNumeric(10);
        $result = $rule->get();
        $func = $result->func;
        $this->assertEquals(
            $func(8, 10),
            '0, 10'
        );
    }

    /**
     * Test grouping rule by regular expressions
     *
     * @return null
    **/
    public function testItShouldGroupByRegex()
    {
        $rule = new Rule();
        $rule->groupRegex('/^([\w\-]+)/i');
        $result = $rule->get();
        $func = $result->func;
        $this->assertEquals(
            $func('apple iphone', '/^([\w\-]+)/i'),
            'apple'
        );
    }
}
