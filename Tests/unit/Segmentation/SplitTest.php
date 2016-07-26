<?php
namespace mfmbarber\CSV_Cruncher\Tests\Unit\Segmentation;
use mfmbarber\CSV_Cruncher\Tests\Mocks as Mocks;
use mfmbarber\CSV_Cruncher\Segmentation\Split as Split;
use mfmbarber\CSV_Cruncher\Segmentation\Query as Query;

class SplitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
    **/
    public function executeSplitWorksCorrectlyHorizontal()
    {
        $data = "email, name, colour, dob, age\n"
        ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
        ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
        ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
        ."no_name@something.com, , \"green\", 01/01/2000, fifteen";
        $customMocks = new Mocks();
        $sourceFile = $customMocks->createMockSourceFile($data);
        $split = new Split();

        $result = $split->fromSource($sourceFile)
            ->horizontal(2)
            ->execute();

        $expected = [
            [
                [
                    'email' => 'mfmbarber@test.com',
                    'name' => 'matt',
                    'colour' => 'black, green, blue',
                    'dob' => '24/11/1987',
                    'age' => 28
                ],
                [
                    'email' => 'matt.barber@test.com',
                    'name' => 'matthew',
                    'colour' => 'red, green',
                    'dob' => '01/12/1980',
                    'age' => 35
                ]
            ],
            [
                [
                    'email' => 'tony.stark@avengers.com',
                    'name' => 'tony',
                    'colour' => 'red, gold',
                    'dob' => '02/05/1990',
                    'age' => 25
                ],
                [
                    'email' => 'no_name@something.com',
                    'name' => '',
                    'colour' => 'green',
                    'dob' => '01/01/2000',
                    'age' => 'fifteen'
                ],
            ]
        ];
        $this->assertEquals(
            $result,
            $expected,
            'Execute didn\'t split the data as expected'
        );
    }
    /**
     * @test
    **/
    public function executeSplitWorksCorrectlyVertical()
    {
        $data = "email, name, colour, dob, age\n"
        ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
        ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
        ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
        ."no_name@something.com, , \"green\", 01/01/2000, fifteen";
        $customMocks = new Mocks();
        $sourceFile = $customMocks->createMockSourceFile($data);
        $split = new Split();
        $result = $split->fromSource($sourceFile)
            ->vertical(['email, name', 'email, age'])
            ->execute();
        $expected = [
            [
                [
                    'email' => 'mfmbarber@test.com',
                    'name' => 'matt'
                ],
                [
                    'email' => 'matt.barber@test.com',
                    'name' => 'matthew'
                ],
                [
                    'email' => 'tony.stark@avengers.com',
                    'name' => 'tony'
                ],
                [
                    'email' => 'no_name@something.com',
                    'name' => ''
                ]
            ],
            [
                [
                    'email' => 'mfmbarber@test.com',
                    'age' => '28'
                ],
                [
                    'email' => 'matt.barber@test.com',
                    'age' => '35'
                ],
                [
                    'email' => 'tony.stark@avengers.com',
                    'age' => '25'
                ],
                [
                    'email' => 'no_name@something.com',
                    'age' => 'fifteen'
                ]
            ],

        ];
        $this->assertEquals(
            $result,
            $expected,
            'The vertical split wasn\'t correct'
        );
    }
    // /**
    //  * @test
    // **/
    // public function executeSplitWorksCorrectlyBilateral()
    // {
    //     $data = "email, name, colour, dob, age\n"
    //     ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
    //     ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
    //     ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
    //     ."no_name@something.com, , \"green\", 01/01/2000, fifteen";
    //     $customMocks = new Mocks();
    //     $sourceFile = $customMocks->createMockSourceFile($data);
    //     $split = new Split();
    //     $result = $split->fromSource($sourceFile)
    //         ->bilateral(['email, name', 'email, age'], 2)
    //         ->execute();
    //     print_r($result);
    //     $this->assertFalse(true);
    // }
}
