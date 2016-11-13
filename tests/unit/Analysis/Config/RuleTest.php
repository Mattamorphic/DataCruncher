<?php
namespace mfmbarber\DataCruncher\Tests\Unit\Analysis\Config;

use mfmbarber\DataCruncher\Analysis\Config\Rule as Rule;
use mfmbarber\DataCruncher\Helpers\Files\CSVFile as CSVFile;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class RuleTest extends \PHPUnit_Framework_TestCase
{
    public function testSetField()
    {
        $rule = new Rule();
        $rule->setField('test');
        $result = $rule->get();
        $this->assertEquals(
            $result['field'],
            'test'
        );
    }

    public function testSetLabel()
    {
        $rule = new Rule();
        $rule->setLabel('test');
        $result = $rule->get();
        $this->assertEquals(
            $result['label'],
            'test'
        );
    }

    public function testGroupExact()
    {
        $rule = new Rule();
        $rule->groupExact();
        $result = $rule->get();
        $this->assertEquals(
            $result['function']('a', null),
            'a'
        );
    }

    public function testGroupNumeric()
    {
        $rule = new Rule();
        $rule->groupNumeric(10);
        $result = $rule->get();
        $this->assertEquals(
            $result['function'](8, 10),
            '0, 10'
        );
    }

    public function testGroupRegex()
    {
        $rule = new Rule();
        $rule->groupRegex('/^([\w\-]+)/i');
        $result = $rule->get();
        $this->assertEquals(
            $result['function']('apple iphone', '/^([\w\-]+)/i'),
            'apple'
        );
    }
}
