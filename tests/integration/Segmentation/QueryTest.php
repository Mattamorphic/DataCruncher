<?php
namespace mfmbarber\Data_Cruncher\Tests\Integration;
use mfmbarber\Data_Cruncher\Segmentation\Query as Query;
use mfmbarber\Data_Cruncher\Helpers\Files\CSVFile as CSVFile;
class QueryTest extends \PHPUnit_Framework_TestCase
{
    const SMALL_TEST = './tests/integration/Segmentation/test_small.csv';

    /**
     * @test
     * @dataProvider queryDataProvider
    **/
    public function executeSmallTestCorrectly($query_data, $expected)
    {
        $sourceFile = new CSVFile();
        $sourceFile->setSource(self::SMALL_TEST, ['modifier' => 'r']);

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
     * @test
     * @dataProvider queryDataDateProvider
    **/
    public function executeSmallTestDateCorrectly($query_data, $expected)
    {
        $sourceFile = new CSVFile();
        $sourceFile->setSource(self::SMALL_TEST, ['modifier' => 'r']);

        $query = new Query();

        $result = $query->fromSource($sourceFile)
            ->select($query_data['select'])
            ->condition($query_data['condition'])
            ->where($query_data['where'][0], $query_data['where'][1])
            ->value($query_data['value'][0], $query_data['value'][1])
            ->execute();

        $this->assertEquals(
            $expected,
            $result,
            "Execute did not return the expected results"
        );
    }
    /**
     * Data provider for executeSmallTestCorrectly
     *
     * @return array
    **/
    public function queryDataProvider()
    {
        return [
            [
                [
                    'select' => ['name'],
                    'where' => 'location',
                    'condition' => 'equals',
                    'value' => 'Dorset'
                ],
                [
                    ['name' => 'Matt'],
                    ['name' => 'Laura'],
                    ['name' => 'Michy'],
                    ['name' => 'Rob'],
                    ['name' => 'Ryan']
                ]
            ]
        ];
    }
    /**
     * Data provider for executeSmallTestDateCorrectly
     *
     * @return array
    **/
    public function queryDataDateProvider()
    {
        return [
            [
                [
                    // Test the between condition
                    'select' => ['email'],
                    'where' => ['dob', 'd/m/Y'],
                    'condition' => 'between',
                    'value' => [['1980', '2000'], 'Y']
                ],
                [
                    ['email' => 'matt.barber@test.com'],
                    ['email' => 'laura.bond@test.com'],
                    ['email' => 'ryan.barber@town.com']
                ]
            ]
        ];
    }
}
