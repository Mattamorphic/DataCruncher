<?php
//
// namespace mfmbarber\Example;
include 'vendor/autoload.php';

use mfmbarber\Data_Cruncher\Helpers\CSVFile as CSVFile;
use mfmbarber\Data_Cruncher\Helpers\XMLFile as XMLFile;

use mfmbarber\Data_Cruncher\Segmentation\Query as Query;
use mfmbarber\Data_Cruncher\Manipulator as Manipulator;

use mfmbarber\Data_Cruncher\Segmentation\Merger as Merger;
use mfmbarber\Data_Cruncher\Segmentation\Split as Split;

// $manip = new Manipulator(new CSVFile(), new Query());
// $outFile = new XMLFile();
// $outFile->setSource('example/output.xml', ['modifier' => 'w']);

// $manip->setDataSource('example/example.csv', []);

// $res = $manip->query()
// ->select(['name', 'email', 'age'])
// ->where('email')
// ->condition('CONTAINS')
// ->value('@')
// ->execute($outFile, 'person', 'people');

// print_r($res);


// $merger = new Merger();
// $source_a = new XMLFile();
// $source_a->setSource('example/example.xml', []);
// $source_b = new XMLFile();
// $source_b->setSource('example/example2.xml', []);
// $result = $merger->fromSource($source_a)
//             ->fromSource($source_b)
//             ->on('name')
//             ->execute(null, 'food', 'breakfast_menu');
// print_r($result);


// $split = new Split();
// $split_source = new CSVFile();
// $split_source->setSource('example/example.csv', []);
// $result = $split->fromSource($split_source)->vertical([['email', 'name'], ['email', 'job']])->execute();
// print_r($result);


// $split = new Split();
// $split_source = new XMLFile();
// $split_source->setSource('example/example.xml', []);
// $result = $split->fromSource($split_source)->vertical([['name', 'price'], ['name', 'description']])->execute([], 'food', 'breakfast_menu');
// print_r($result);
