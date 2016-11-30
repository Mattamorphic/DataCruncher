<?php
namespace mfmbarber\DataCruncher\Tests\Integration\CSVTests;

use mfmbarber\DataCruncher\Helpers\DataSource;
use mfmbarber\DataCruncher\Analysis\Statistics;
use mfmbarber\DataCruncher\Analysis\Config\Rule;
use mfmbarber\DataCruncher\Segmentation\Query;
use mfmbarber\DataCruncher\Segmentation\Merger;

/**
 * Test the CSV File object end to end using components
 *
 * @author Matt Barber <mfmbarber@gmail.com>
 * @package DataCruncher : Integration Tests
 *
**/
class CSVTest extends \PHPUnit_Framework_TestCase
{
    private $file;

    public function __construct()
    {
        //parent::construct();
        $this->dir = getcwd() . '/tests/integration/CSVTests';
        $this->file = "{$this->dir}/InputFiles/1000row6columndata.csv";
    }

    public function setup()
    {
        $this->sourceCSV = DataSource::generate('file', 'csv');
        $this->sourceCSV->setSource($this->file, ['modifier' => 'r']);
        $this->sourceCSV->sort('id', true, false);
    }

    public function tearDown()
    {
        // let's remove any outputfiles.
        $dir = "{$this->dir}/OutputFiles";
        if (false !== ($files = scandir($dir))) {
            array_map(
                function ($file) use ($dir) {
                    // avoid . and ..
                    $path = pathinfo("$dir/$file");
                    if (strlen($file) > 2 && $path['extension'] !== '.md') {
                        unlink("$dir/$file");
                    }
                },
                $files
            );
        }
    }

    public function testItShouldSortTheResultsByString()
    {
        $this->sourceCSV->sort('first_name', false, false);
        $this->sourceCSV->open();
        $row = $this->sourceCSV->getNextDataRow();
        $this->sourceCSV->close();
        $this->assertEquals(
            $row,
            [
                'id' => 701,
                'first_name' => 'Aaron',
                'last_name' => 'Collins',
                'email' => 'acollinsjg@qq.com',
                'gender' => 'Male',
                'ip_address' => '41.138.204.207'
            ]
        );
    }

    public function testItShouldSortTheResultByInt()
    {
        $this->sourceCSV->sort('id', true, false);
        $this->sourceCSV->open();
        $row = $this->sourceCSV->getNextDataRow();
        $this->sourceCSV->close();
        $this->assertEquals(
                $row,
                [
                    'id' => 1,
                    'first_name' => 'Paul',
                    'last_name' => 'Simmons',
                    'email' => 'psimmons0@state.gov',
                    'gender' => 'Male',
                    'ip_address' => '139.134.139.43'
                ]
        );

    }

    /**
     * As a user I want to query a CSV file and get back an array
     *
     * @return null
    **/
    public function testItShouldReturnAnArrayOnceQueried()
    {
        $query = new Query();
        $result = $query->fromSource($this->sourceCSV)
            ->select(['id', 'first_name'])
            ->where('email')
            ->condition('CONTAINS')
            ->value('stumbleupon')
            ->execute();
        $this->assertEquals(count($result), 4);

    }

    /**
     * As a user I want to query a CSV file and get back a CSV file
     *
     * @return null
    **/
    public function testItShouldCreateAnOutFileOnceQueried()
    {
        $query = new Query();
        $out = DataSource::generate('file', 'csv');
        $out->setSource(
            "{$this->dir}/OutputFiles/EmailContainsStumbleUpon.csv",
            ['modifier' => 'w']
        );
        $result = $query->fromSource($this->sourceCSV)
            ->select(['id', 'first_name'])
            ->where('email')
            ->condition('CONTAINS')
            ->value('stumbleupon')
            ->execute($out);
        $this->assertEquals(
            file_get_contents(
                "{$this->dir}/OutputFiles/EmailContainsStumbleUpon.csv"
            ),
            "id,first_name\n".
            "11,Clarence\n".
            "225,Ashley\n".
            "240,Catherine\n".
            "852,Sharon\n"
        );
    }

    /**
     * As a user I want to query a CSV file and get back the results as a file
     * calculating elapsed time and memory. This should take less than 10 miliseconds
     *
     * @return null
    **/
    public function testItShouldReturnAnExecutionTimeAndMemoryUsage()
    {
        $query = new Query();
        $out = Datasource::generate('file', 'csv');
        $out->setSource(
            "{$this->dir}/OutputFiles/EmailContainsStumbleUpon.csv",
            ['modifier' => 'w']
        );
        $result = $query->fromSource($this->sourceCSV)
            ->select(['id', 'first_name', 'ip_address'])
            ->where('email')
            ->condition('CONTAINS')
            ->value('stumbleupon')
            ->execute($out, null, true);
        $this->assertTrue(isset($result['timer']));
        $this->assertTrue(
            isset($result['timer']['elapsed']) &&
            isset($result['timer']['memory'])
        );
        $this->assertTrue(
            $result['timer']['elapsed'] < 10,
            "Timer took longer than 10 miliseconds - somethings up!"
        );
    }

    /**
     * As a user I want to query a CSV file, and get back the results as an XML file
     *
     * @return null
    **/
    public function testItShouldReturnAnXMLFile()
    {
        $query = new Query();
        $out = DataSource::generate('file', 'xml', 'person', 'people');
        $out->setSource(
            "{$this->dir}/OutputFiles/EmailContainsStumbleUpon.xml",
            ['modifier' => 'w']
        );
        $result = $query->fromSource($this->sourceCSV)
            ->select(['id', 'first_name'])
            ->where('email')
            ->condition('CONTAINS')
            ->value('stumbleupon')
            ->execute($out);
        $xml = preg_replace('/\s+/', '', file_get_contents(
            "{$this->dir}/OutputFiles/EmailContainsStumbleUpon.xml"
        ));
        $expected_xml = preg_replace('/\s+/', '', "
        <?xml version=\"1.0\"?>
        <people>
            <person>
                <id>11</id>
                <first_name>Clarence</first_name>
            </person>
            <person>
                <id>225</id>
                <first_name>Ashley</first_name>
            </person>
            <person>
                <id>240</id>
                <first_name>Catherine</first_name>
            </person>
            <person>
                <id>852</id>
                <first_name>Sharon</first_name>
            </person>
        </people>");
        $this->assertEquals($xml, $expected_xml);
    }

    /**
     *  As a user I want to get the statitics of a CSV file using one rule
     *
     * @return null
    **/
    public function testItShouldReturnStatistics()
    {
        $stats = new Statistics();
        $rule = new Rule();
        $rule = $rule->setField('gender')->groupExact();
        $stats->addRule($rule);
        $result = $stats->fromSource($this->sourceCSV)
            ->execute();
        $result = array_pop($result);
        $this->assertEquals(
            [
                'Male' => 534,
                'Female' => 466
            ],
            $result,
            "Execute did not return the expected results"
        );

    }

    /**
     * As a user I want to get the statistics of a CSV file using multiple rules
     * @return null
    **/
    public function testItShouldReturnMultipleStatistics()
    {
        $stats = new Statistics();

        $rule = new Rule();
        $rule = $rule->setField('gender')->groupExact();
        $stats->addRule($rule);

        $rule = new Rule();
        $rule = $rule->setField('email')->groupRegex('/(((?<=\.)\w{2,4}\.\w{2,4}|(?<=\.)\w{2,4}))$/i');
        $stats->addRule($rule);

        $result = $stats->fromSource($this->sourceCSV)
            ->execute();
        $this->assertEquals(
            $result[0]['Male'],
            534,
            "Execute did not return the expected results"
        );
        $this->assertEquals(
            $result[1]['gov'],
            57,
            "Execute did not return the expected results"
        );
    }

    /**
     * As a user I want to query a CSV and get back the results as a CSV file, but with different mappings
     *
     * @return null
    **/
    public function testItShouldRemapTheOutput()
    {
        $query = new Query();
        $out = Datasource::generate('file', 'csv');
        $out->setSource(
            "{$this->dir}/OutputFiles/EmailContainsStumbleUpon.csv",
            ['modifier' => 'w']
        );
        $result = $query->fromSource($this->sourceCSV)
            ->select(['id', 'first_name', 'ip_address'])
            ->where('email')
            ->condition('CONTAINS')
            ->value('stumbleupon')
            ->execute($out, [
                'first_name' => 'Forename',
                'ip_address' => 'IP'
            ]);
        $this->assertEquals(
                file_get_contents("{$this->dir}/OutputFiles/EmailContainsStumbleUpon.csv"),
                "id,Forename,IP\n".
                "11,Clarence,216.116.2.195\n".
                "225,Ashley,8.240.98.205\n".
                "240,Catherine,222.24.26.173\n".
                "852,Sharon,0.207.225.77\n"
        );


    }

    /**
     * As a user I want to query a CSV file and get back the results as a sorted CSV file
     *
     * @return null
    **/
    public function testItShouldSortTheResults()
    {
        // $query = new Query();
        // $out = Datasource::generate('file', 'csv');
        // $out->setSource(
        //     "{$this->dir}/OutputFiles/EmailContainsStumbleUpon.csv",
        //     ['modifier' => 'w']
        // );
        // $result = $query->fromSource($this->sourceCSV)
        //     ->select(['id', 'email'])
        //     ->where('email')
        //     ->condition('CONTAINS')
        //     ->value('stumbleupon')
        //     ->execute($out);
        // $this->assertEquals(
        //     file_get_contents("{$this->dir}/OutputFiles/EmailContainsStumbleUpon.csv"),
        //     "id\n225",
        //     "Doesn't meet expected response"
        // );
    }

    /**
     * As a user I want to limit the amount of results from a queried CSV file as an array
     *
     * @return null
    **/
    public function testItShouldLimitTheAmountOfResults()
    {
        $query = new Query();
        $result = $query->fromSource($this->sourceCSV)
            ->select(['id'])
            ->where('email')
            ->condition('CONTAINS')
            ->value('stumbleupon')
            ->limit(1)
            ->execute();
        $this->assertEquals(
            $result,
            [['id' => 11]]
        );

    }

    /**
     * As a user I want to merge two CSV files together into a single file, based on the email column
     *
     * @return null
    **/
    public function testItShouldMergeTwoFiles()
    {
        $merger = new Merger();
        $csv = Datasource::generate('file', 'csv');
        $csv->setSource(
            "{$this->dir}/InputFiles/MergeRows.csv",
            ['modifier' => 'r']
        );
        $result = $merger->fromSource($this->sourceCSV)
            ->fromSource($csv)
            ->on('email')
            ->execute();
        $this->assertEquals(
            $result,
            [
                [
                    "id" => 1,
                    "first_name" => "Paul",
                    "last_name" => "Simmons",
                    "email" => "psimmons0@state.gov",
                    "gender" => "Male",
                    "ip_address" => "139.134.139.43",
                    "age" => 36
                ]
            ]
        );
    }


}
