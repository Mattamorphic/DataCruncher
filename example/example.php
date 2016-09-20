<?php
// namespace mfmbarber\Example;
include 'vendor/autoload.php';

// use mfmbarber\Data_Cruncher\Helpers\CSVFile as CSVFile;
// use mfmbarber\Data_Cruncher\Helpers\XMLFile as XMLFile;
// use mfmbarber\Data_Cruncher\Helpers\CSVOutput as CSVOutput;

use mfmbarber\Data_Cruncher\Helpers\DataSource as DataSource;

use mfmbarber\Data_Cruncher\Helpers\DataSource as DataSource;

use mfmbarber\Data_Cruncher\Analysis\Statistics as Statistics;
use mfmbarber\Data_Cruncher\Analysis\Config\Rule as Rule;
use mfmbarber\Data_Cruncher\Segmentation\Query as Query;
use mfmbarber\Data_Cruncher\Manipulator as Manipulator;

use mfmbarber\Data_Cruncher\Segmentation\Merger as Merger;
use mfmbarber\Data_Cruncher\Segmentation\Split as Split;

echo date('H:i:s');
echo "\n";
$file = DataSource::generate('file', 'csv');
$file->setSource('./example/example2.csv');
$stats = new Statistics();

$rule = new Rule();
$rule->setField('phone')->groupRegex('/^([\w\-]+)/i')->setLabel('phone type');
$stats->addRule($rule);
$rule = new Rule();
$rule->setField('colour')->groupRegex('/([^,]+)/');
$stats->addRule($rule);

$result = $stats->fromSource($file)
    ->percentages()
    ->execute();

print_r($result);
echo "\n";
echo date('H:i:s');
