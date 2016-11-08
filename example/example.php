<?php
// namespace mfmbarber\Example;
include 'vendor/autoload.php';

// use mfmbarber\Data_Cruncher\Helpers\CSVFile as CSVFile;
// use mfmbarber\Data_Cruncher\Helpers\XMLFile as XMLFile;
// use mfmbarber\Data_Cruncher\Helpers\CSVOutput as CSVOutput;

use mfmbarber\DataCruncher\Helpers\DataSource as DataSource;


use mfmbarber\DataCruncher\Analysis\Statistics as Statistics;
use mfmbarber\DataCruncher\Analysis\Config\Rule as Rule;
use mfmbarber\DataCruncher\Segmentation\Query as Query;
use mfmbarber\DataCruncher\Manipulator as Manipulator;

use mfmbarber\DataCruncher\Segmentation\Merger as Merger;
use mfmbarber\DataCruncher\Segmentation\Split as Split;

echo date('H:i:s');
echo "\n";
$db = DataSource::generate('db', 'sql');
$db->setSource('TEST', [
    'username' => 'root',
    'password' => '',
    'table' => 'users'
]);

$outfile = DataSource::generate('file', 'csv');
$outfile->setSource('./example/outfile3.csv', ['modifier' => 'wb']);

$query = new Query();

$result = $query
->fromSource($db)
->select(['firstname', 'lastname'])
->where('email')
->condition('CONTAINS')
->value('gmail.com')
->execute();


//$stats = new Statistics();

//$rule = new Rule();
//$rule->setField('phone')->groupRegex('/^([\w\-]+)/i')->setLabel('phone type');
//$stats->addRule($rule);
//$rule = new Rule();
//$rule->setField('colour')->groupRegex('/([^,]+)/');
//$stats->addRule($rule);

//$result = $stats->fromSource($file)
//    ->percentages()
//    ->execute();

print_r($result);
echo "\n";
echo date('H:i:s');
