<?php
namespace mfmbarber\CSV_Cruncher\Tests\Unit\Segmentation;
use mfmbarber\CSV_Cruncher\Tests\Mocks as Mocks;
use mfmbarber\CSV_Cruncher\Segmentation\Merger as Merger;

class MergerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
    **/
    public function executeMergeOnEmailAddressCorrectlyMerges()
        {
        $data = "email, name\n"
        ."mfmbarber@test.com, matt \n"
        ."matt.barber@test.com, matthew\n"
        ."tony.stark@avengers.com, tony\n"
        ."no_name@something.com, ";

        $data_merge = "email, occupation\n"
        ."mfmbarber@test.com, Developer\n"
        ."matt.barber@test.com, Support\n"
        ."tony.stark@avengers.com, IronMan\n"
        ."hulk@avengers.com, Scientist";
        $customMocks = new Mocks();
        $sourceFile = $customMocks->createMockSourceFile($data);
        $mergeFile = $customMocks->createMockSourceFile($data_merge);
        $merger = new Merger();

        $result = $merger->fromSource($sourceFile)
            ->fromSource($mergeFile)
            ->on('email')
            ->execute();

        $expected = [
            [
                'email' => 'mfmbarber@test.com',
                'name' => 'matt',
                'occupation' => 'Developer'
            ],
            [
                'email' => 'matt.barber@test.com',
                'name' => 'matthew',
                'occupation' => 'Support'
            ],
            [
                'email' => 'tony.stark@avengers.com',
                'name' => 'tony',
                'occupation' => 'IronMan'
            ]//,
            // [
            //     'email' => 'no_name@something.com',
            //     'name' => '',
            //     'occupation' => ''
            // ],
            // [
            //     'email' => 'hulk@avengers.com',
            //     'name' => '',
            //     'occupation' => 'Scientist'
            // ]
        ];
        $this->assertEquals(
            $result,
            $expected,
            'Merging didn\'t correctly output'
        );
    }
}
