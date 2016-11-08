<?php
/**
 * Abstract Database Handler (shared methods)
 *
 * @package DataCruncher
 * @subpackage Helpers
 * @author matt barber <mfmbarber@gmail.com>
 *
 */
declare(strict_types=1);
namespace mfmbarber\DataCruncher\Helpers\Databases;

use mfmbarber\DataCruncher\Exceptions;
use mfmbarber\DataCruncher\Config\Validation;
use mfmbarber\DataCruncher\Helpers\Interfaces\DataInterface;

class Database implements DataInterface
{
    const CONDITIONS = [
        'EQUALS' => '=',
        'GREATER' => '>',
        'LESS' => '<',
        'NOT' => 'NOT',
        'BETWEEN' => 'BETWEEN',
        'NOT_BETWEEN' => 'NOT BETWEEN',
        'EMPTY' => 'IS NULL',
        'NOT_EMPTY' => 'IS NOT NULL',
        'CONTAINS' => 'LIKE',
        'IN'    => 'IN'
    ];

    public $_connection = null;
    public $_dsn;

    private $_username;
    private $_password;
    private $_table;

    private $_query = null;
    private $_headers = [];

    /**
     * Sets the source for the DATABASE manipulator object.
     * At this stage it simply defines the state
     *
     * @param string    $db             The name of the database
     * @param array     $properties     A hash table defining specific properties
     *                                  This must contain username, password and table
     * @return null
    **/
    public function setSource(string $db, array $properties)
    {
        $type = $properties['type'] ?? 'mysql';
        $host = $properties['host'] ?? 'localhost';
        $this->setDSN($type, $db, $host);

        if (!isset($properties['username'])) {
            throw new \Exception('A username key must be passed in the properties array');
        }
        if (!isset($properties['password'])) {
            throw new \Exception('A password key must be passed in the properties array');
        }
        if (!isset($properties['table'])) {
            throw new \Exception('A table key must be passed in the properties array');
        }

        $this->_username = $properties['username'];
        $this->_password = $properties['password'];

        $this->setTable($properties['table']);
    }

    /**
     * A public setter method that allows us to change the table in the target DB
     * if required
     *
     * @param string    $table  The table to point to
     *
     * @return null
    **/
    public function setTable(string $table)
    {
        $this->_table = $table;
    }

    /**
     * A getter method that returns the headers on the table
     * This will always force a refresh unless specifically told not to
     *
     * @param bool  $force  Should the headers be reset? Defaults to true
     *
     * @return array
    **/
    public function getHeaders(bool $force = true) : array
    {
        if ($force || $this->_headers === []) {
            $desc = $this->_connection->prepare("DESCRIBE {$this->_table}");
            $desc->execute();
            $this->_headers = $desc->fetchAll(\PDO::FETCH_COLUMN);
        }
        return $this->_headers;
    }

    /**
     * Create a DB connection and store it in the state of the object using PDO
     *
     * @return null
    **/
    public function open()
    {
        try {
            $this->_connection = new \PDO($this->_dsn, $this->_username, $this->_password);
        } catch (\PDOException $e) {
            // TODO Throw a kinder exception
        }

    }

    /**
     * Kill a connection object
     *
     * @return null
    **/
    public function close()
    {
        $this->_connection = null;
    }

    /**
     * Execute a PDO prepared statement and store the result in the state attribute
     * _query -> think of this as our _fp
     *
     * @param array     $fields     The return fields for the request
     * @param string    $where      The name of the field to query
     * @param string    $condition  The condition to execute
     * @param mixed     $value      The value for the condition
     *
     * @return null
    **/
    public function query($fields, $where, $condition, $value)
    {
        // TODO a fuck tonne of parsing of where  condition and value based on constant CONDITIOND
        $condition = self::CONDITIONS[$condition];
        switch ($condition) {
            case 'LIKE':
                $value = "%$value%";
            break;
            case 'IN':
                $value = '('. implode(',', $value) .')';
            break;
        }
        $sql = 'SELECT ' . implode(', ', array_keys($fields)) . " FROM {$this->_table} WHERE $where $condition :value";
        $this->_query = $this->_connection->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $this->_query->execute([':value' => $value]);

    }

    /**
     * Returns the connection status for the object
     *
     * @return mixed
    **/
    public function getSourceName() : string
    {
        if ($this->_connection === null) {
            return null;
        }
        return '';
    }

    /**
     * Return the next row from the result
     *
     * @return array
    **/
    public function getNextDataRow()
    {
        if ($this->_query !== null && ($line = $this->_query->fetch(\PDO::FETCH_ASSOC))) {
            return $line;
        } else {
            return [];
        }
    }
    public function writeDataRow(array $row)
    {
        // insert into

    }
    /**
     * Sets the DSN for the PDO object held in connection
     * @param string    $type   The DB type, i.e. mysql
     * @param string    $db     The name of the target DB
     * @param string    $host   The location of the DB
    **/
    private function setDSN(string $type, string $db, string $host)
    {
        $this->_dsn = "$type:dbname=$db;host=$host";
    }


}
