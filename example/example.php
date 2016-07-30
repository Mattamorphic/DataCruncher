<?php
//
// namespace mfmbarber\Example;
include 'vendor/autoload.php';
use mfmbarber\Data_Cruncher\Helpers\CSVFile as CSVFile;
use mfmbarber\Data_Cruncher\Segmentation\Query as Query;
use mfmbarber\Data_Cruncher\Manipulator as Manipulator;

$manip = new Manipulator(new CSVFile(), new Query());
$outFile = new CSVFile();
$outFile->setSource('example/output.csv', ['modifier' => 'w']);
$manip->setDataSource('example/example.csv', []);
$res = $manip->query()
->select(['email'])
->where('email')
->condition('CONTAINS')
->value('mfmbarber')
->execute($outFile);


print_r($res);
