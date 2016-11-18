<?php
namespace mfmbarber\DataCruncher\Tests\Unit\Helpers;

use mfmbarber\DataCruncher\Helpers\Databases\Database as Database;
use mfmbarber\DataCruncher\Segmentation\Query as Query;

class DatabaseTest extends \PHPUnit_Extensions_Database_TestCase
{
    protected $pdo = null;

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection()
    {
        if (null === $this->pdo) {
            /*
                After creating our PDO object, create a dummy table
                Create default db connection deletes all records prior to
                running getDataSet
            */
            $this->pdo = new \PDO('sqlite::memory:');
            $this->pdo->exec('CREATE table users(
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                colour TEXT NOT NULL,
                age INT NOT NULL
            )');
        }
        return $this->createDefaultDBConnection($this->pdo, ':memory:');
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__)."/_files/users.yml"
        );
    }

    public function  setUp()
    {
        parent::setUp();
        $this->mockDB = new Database();
        $this->mockDB->setSource('TEST', [
            'username' => '',
            'password' => '',
            'table' => 'users'
        ]);
        $this->mockDB->_connection = $this->getConnection()->getConnection();
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->mockDB = null;
    }

    /**
     * Return the configuration for this source helper
    **/
    public function testItShouldReturnValidSourceName()
    {
        $this->assertEquals(
            $this->mockDB->getSourceName(),
            'mysql:dbname=TEST;host=localhost'
        );

    }
    /**
     * Test that the Database helper returns the next row from the result
    **/
    public function testItShouldReturnTheFirstResult()
    {
        $this->mockDB->query(['name' => 0, 'email' => 1]);
        $this->assertEquals(
            $this->mockDB->getNextDataRow(),
            [
                'name' => 'matt',
                'email' => 'mfmbarber@gmail.com'
            ]
        );
    }

    /**
     * Return the current headers
    **/
    public function testItShouldReturnHeaders()
    {
        $this->assertEquals(
            $this->mockDB->getHeaders(),
            [
                'id',
                'name',
                'email',
                'colour',
                'age'
            ]
        );
    }

    /**
     * Test writing a data row
    **/
    public function testItShouldWriteLineToTheDB()
    {
        $this->mockDB->writeDataRow(
            [
                'name' => 'Tony',
                'email' => 'tony@starkindustries.com',
                'colour' => 'red',
                'age' => '36'
            ]
        );

        $this->mockDB->query(['email' => 1], 'name', 'CONTAINS', 'Tony');
        $this->assertEquals(
            ['email' => 'tony@starkindustries.com'],
            $this->mockDB->getNextDataRow()
        );
    }

    /**
     * Test sorting a database table during execution
     * @test
    **/
    public function sortData()
    {
        $this->mockDB->sort('age');
        $this->mockDB->query(['name' => 1], 'id', 'NOT_EMPTY');
        $this->assertEquals(
            ['name' => 'ryan'],
            $this->mockDB->getNextDataRow()
        );
    }
}
