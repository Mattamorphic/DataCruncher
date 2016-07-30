<?php
namespace mfmbarber\Data_Cruncher\Tests\Unit\Segmentation;
use mfmbarber\Data_Cruncher\Tests\Mocks as Mocks;
use mfmbarber\Data_Cruncher\Segmentation\Query as Query;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that query execution returns the appropriate array (structure /
     * values)
     *
     * @test
     * @dataProvider queryDataProvider
     * @param        array $query_data The data for the test
     * @param        array $expected   The expected result, given the query_data
     *
     * @return null
    **/
    public function executeQueryWorksCorrectly($query_data, $expected)
    {
        $data = "email, name, colour, dob, age\n"
        ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
        ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
        ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
        ."no_name@something.com, , \"green\", 01/01/2000, fifteen";
        $customMocks = new Mocks();
        $sourceFile = $customMocks->createMockSourceFile($data);
        $query = new Query();

        $result = $query->fromSource($sourceFile)
            ->select($query_data['select'])
            ->where($query_data['where'])
            ->condition($query_data['condition'])
            ->value($query_data['value'])
            ->execute();

        $this->assertEquals(
            $expected,
            $result,
            "Execute did not return the expected results"
        );
    }

    /**
     * Tests that query (with dates) execution returns the appropriate array
     * (struture / values)
     *
     * @test
     * @dataProvider queryDateDataProvider
     * @param        array $query_data The data for the test
     * @param        array $expected   The expected result, given the query_data
     *
     * @return null
    **/
    public function executeQueryDatesWorkCorrectly($query_data, $expected)
    {
        $data = "email, name, colour, dob, age\n"
        ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
        ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
        ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
        ."no_name@something.com, , \"green\", 01/06/2000, fifteen";
        $customMocks = new Mocks();
        $sourceFile = $customMocks->createMockSourceFile($data);
        $query = new Query();

        // This improves readability over call_user_func_array
        $whereField = $query_data['where'][0];
        $whereFormat = $query_data['where'][1];
        $matchVal = $query_data['value'][0];
        $matchFormat = $query_data['value'][1];

        $result = $query->fromSource($sourceFile)
            ->select($query_data['select'])
            ->condition($query_data['condition'])
            ->where($whereField, $whereFormat)
            ->value($matchVal, $matchFormat)
            ->execute();
        $this->assertEquals(
            $expected,
            $result,
            "Execute did not return the expected results"
        );
    }
    /**
     * Unit test, date format or value in value clause of query is incorrect
     *
     * @test
     * @expectedException        mfmbarber\Data_Cruncher\Exceptions\InvalidDateValueException
     * @expectedExceptionMessage Couldn't create datetime object from value/dateFormat - please check
     *
     * @return null
    **/
    public function executeQueryDateThrowsException()
    {
        $data = "email, name, colour, dob, age\n"
        ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
        ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
        ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
        ."no_name@something.com, , \"green\", 01/06/2000, fifteen";
        $customMocks = new Mocks();
        $sourceFile = $customMocks->createMockSourceFile($data);
        $query = new Query();

        $result = $query->fromSource($sourceFile)
            ->select(['dob'])
            ->condition('after')
            ->where('dob', 'd/m/Y')
            ->value('not a date', 'Y')
            ->execute();
    }
    /**
     * Given the wrong data type for the select method, throw error
     *
     * @test
     * @expectedException        mfmbarber\Data_Cruncher\Exceptions\ParameterTypeException
     * @expectedExceptionMessage The parameter type for this method was incorrect, expected a normal array
     *
     * @return null
    **/
    public function selectThrowsParameterException()
    {
        $data = "email, name, colour, dob, age\n"
        ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
        ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
        ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
        ."no_name@something.com, , \"green\", 01/06/2000, fifteen";
        $customMocks = new Mocks();
        $sourceFile = $customMocks->createMockSourceFile($data);
        $query = new Query();

        $result = $query->fromSource($sourceFile)
            ->select('dob')
            ->condition('equals')
            ->where('name')
            ->value('matt')
            ->execute();
    }

    /**
     * Given the wrong data type for the where method, throw error
     *
     * @test
     * @expectedException        mfmbarber\Data_Cruncher\Exceptions\ParameterTypeException
     * @expectedExceptionMessage The parameter type for this method was incorrect, expected a string field name
     *
     * @return null
    **/
    public function whereThrowsParameterException()
    {
        $data = "email, name, colour, dob, age\n"
        ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
        ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
        ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
        ."no_name@something.com, , \"green\", 01/06/2000, fifteen";
        $customMocks = new Mocks();
        $sourceFile = $customMocks->createMockSourceFile($data);
        $query = new Query();

        $result = $query->fromSource($sourceFile)
            ->select(['dob'])
            ->condition('equals')
            ->where(['name'])
            ->value('matt')
            ->execute();
    }
    /**
     * Given an incorrect condition throw an InvalidValueException
     *
     * @test
     * @expectedException        mfmbarber\Data_Cruncher\Exceptions\InvalidValueException
     *
     * @return null
    **/
    public function invalidConditionThrowsValueException()
    {
        $data = "email, name, colour, dob, age\n"
        ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
        ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
        ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
        ."no_name@something.com, , \"green\", 01/06/2000, fifteen";
        $customMocks = new Mocks();
        $sourceFile = $customMocks->createMockSourceFile($data);
        $query = new Query();

        $result = $query->fromSource($sourceFile)
            ->select(['dob'])
            ->condition('might be like')
            ->where('name')
            ->value('matt')
            ->execute();
    }
    /**
     * Tests that execution can write to a file, and return a count of the rows
     * affected
     *
     * @test
     *
     * @return null
    **/
    public function executeWriteToFile()
    {
        $data = "email, name, colour, dob, age\n"
        ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
        ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
        ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
        ."no_name@something.com, , \"green\", 01/01/2000, fifteen";
        $customMocks = new Mocks();
        $sourceFile = $customMocks->createMockSourceFile($data);
        $outFile = $customMocks->createMockOutFile();
        $query = new Query();

        $result = $query->fromSource($sourceFile)
            ->select(['email'])
            ->where('name')
            ->condition('contains')
            ->value('matt')
            ->execute($outFile);

        $this->assertEquals(
            2,
            $result,
            "Execute did not return the expected results"
        );
    }
    /**
     * Data provider for executeContainsWorksCorrectly
     *
     * @return array
    **/
    public function queryDataProvider()
    {
        return [
            // Query and expected response - array of rows
            [
                [
                    // Test the equals condition, returns single field
                    'select' => ['email'],
                    'where' => 'name',
                    'condition' => 'equals',
                    'value' => 'matt'
                ],
                [
                    ['email' => 'mfmbarber@test.com']
                ]
            ],
            [
                [
                    // Test the contains condition, returns multiple fields
                    'select' => ['name', 'age'],
                    'where' => 'colour',
                    'condition' => 'contains',
                    'value' => 'red'
                ],
                [
                    ['name' => 'matthew', 'age' => 35],
                    ['name' => 'tony', 'age' => 25]
                ]
            ],
            [
                [
                    // Test the greater than condition
                    'select' => ['name', 'dob'],
                    'where' => 'age',
                    'condition' => 'greater',
                    'value' => 30
                ],
                [
                    ['name' => 'matthew', 'dob' => '01/12/1980']
                ]
            ],
            [
                [
                    // Test the less than condition
                    'select' => ['name'],
                    'where' => 'age',
                    'condition' => 'less',
                    'value' => 30
                ],
                [
                    ['name' => 'matt'],
                    ['name' => 'tony'],
                    ['name' => '']
                ]
            ],
            [
                [
                    // Test the less than condition
                    'select' => ['age'],
                    'where' => 'name',
                    'condition' => 'not',
                    'value' => 'tony'
                ],
                [
                    ['age' => '28'],
                    ['age' => '35'],
                    ['age' => 'fifteen']
                ]
            ],
            [
                [
                    // Test the in condition
                    'select' => ['email', 'colour'],
                    'where' => 'name',
                    'condition' => 'in',
                    'value' => ['matt', 'tony']
                ],
                [
                    [
                        'email' => 'mfmbarber@test.com',
                        'colour' => 'black, green, blue'
                    ],
                    [
                        'email' => 'tony.stark@avengers.com',
                        'colour' => 'red, gold'
                    ]
                ]
            ],
            [
                [
                    // Test the in condition (string)
                    'select' => ['email', 'colour'],
                    'where' => 'name',
                    'condition' => 'in',
                    'value' => 'matt, tony'
                ],
                [
                    [
                        'email' => 'mfmbarber@test.com',
                        'colour' => 'black, green, blue'
                    ],
                    [
                        'email' => 'tony.stark@avengers.com',
                        'colour' => 'red, gold'
                    ]
                ]            ],
            [
                [
                    // Test the empty condition
                    'select' => ['colour', 'dob'],
                    'where' => 'name',
                    'condition' => 'empty',
                    'value' => null
                ],
                [
                    ['colour' => 'green', 'dob' => '01/01/2000']
                ]
            ],
            [
                [
                    // Test the not empty condition
                    'select' => ['dob'],
                    'where' => 'name',
                    'condition' => 'not_empty',
                    'value' => null
                ],
                [
                    ['dob' => '24/11/1987'],
                    ['dob' => '01/12/1980'],
                    ['dob' => '02/05/1990']
                ]
            ],
        ];
    }
    /**
     * Data provider for executeQueryDatesWorkCorrectly
     *
     * @return array
    **/
    public function queryDateDataProvider()
    {
        return [
            [
                [
                    // Test the after condition
                    'select' => ['email'],
                    'where' => ['dob', 'd/m/Y'],
                    'condition' => 'after',
                    'value' => ['1995', 'Y']
                ],
                [
                    ['email' => 'no_name@something.com']
                ]
            ],
            [
                [
                    // Test the after condition
                    'select' => ['email'],
                    'where' => ['dob', 'd/m/Y'],
                    'condition' => 'before',
                    'value' => ['1990', 'Y']
                ],
                [
                    ['email' => 'mfmbarber@test.com'],
                    ['email' => 'matt.barber@test.com']
                ]
            ],
            [
                [
                    // Test the between condition
                    'select' => ['email'],
                    'where' => ['dob', 'd/m/Y'],
                    'condition' => 'between',
                    'value' => [['1980', '2000'], 'Y']
                ],
                [
                    ['email' => 'mfmbarber@test.com'],
                    ['email' => 'matt.barber@test.com'],
                    ['email' => 'tony.stark@avengers.com']
                ]
            ],
            [
                [
                    // Test the not between condition
                    'select' => ['email'],
                    'where' => ['dob', 'd/m/Y'],
                    'condition' => 'not_between',
                    'value' => [['1985', '1995'], 'Y']
                ],
                [
                    ['email' => 'matt.barber@test.com'],
                    ['email' => 'no_name@something.com']
                ]
            ],
            [
                [
                    // Test the on condition
                    'select' => ['email'],
                    'where' => ['dob', 'd/m/Y'],
                    'condition' => 'on',
                    'value' => ['01/06/2000', 'd/m/Y']
                ],
                [
                    ['email' => 'no_name@something.com']
                ]
            ]
        ];
    }

}
