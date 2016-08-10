[![Build Status](https://travis-ci.org/Matt-Barber/DataCruncher.svg?branch=master)](https://travis-ci.org/Matt-Barber/DataCruncher)
[![Coverage Status](https://coveralls.io/repos/github/Matt-Barber/DataCruncher/badge.svg?branch=master)](https://coveralls.io/github/Matt-Barber/DataCruncher?branch=master)

# DataCruncher :page_facing_up:
DataCruncher is a PHP library allowing XML and CSV files (currently) to be queried as you might query a database table (with added extras).
This allows you to segment your data efficiently and pipe this to other outsources. There is support as well for returning arrays - but beware this will
require loading the array into memory and blarg. 

This has been designed with both a Front Controller pattern giving you access to a Manuiplator object as well as a set of Decoupled self contained components


## Installation
The easiest way to install this is probably to git clone this into an extensions folder in your project, then update your composer.json with an autoloader reference. 
It will, once complete, be uploaded to packagist and installed via composer - woo! 

## Usage
Using a decoupled component, i.e. the Query object 
```php
<?php 
// include your autoloader
include 'vendor/autoload.php';
// a helper class for my source file (implements DataInterface)
use mfmbarber\Data_Cruncher\Helpers\CSVFile as CSVFile;
// our query class
use mfmbarber\Data_Cruncher\Segmentation\Query as Query;

$query = new Query();
$file = new CSVFile();

/*
 input.csv is a file like...
 email, name, age
 mfmbarber@gmail.com, matt, 28
 tonystart@gmail.com, tony, 35
*/
$file->setSource('example/input.csv', []);
$result = $query->fromSource($file)
    ->select(['name', 'age'])
    ->where('email')
    ->condition('CONTAINS')
    ->value('gmail')
    ->execute();
// will return an array of associative arrays with each element
// having name and age as keys, and repective values for records where
// email contains gmail 

// We can also specify an outfile 
$outfile = new CSVFile();
$outfile->setSource('example/output.csv', ['modifier' => 'w']);
$result = $query->fromSource($file)
    ->select(['name', 'age'])
    ->where('email')
    ->condition('CONTAINS')
    ->value('gmail')
    ->execute($outfile);
// Which will write the lines to a CSV file 

// Or even 
$outfile = new XMLFile();
$outfile->setSource('example/output.xml', ['modifier' => 'w']);
$result = $query->fromSource($file)
    ->select(['name', 'age'])
    ->where('email')
    ->condition('CONTAINS')
    ->value('gmail')
    ->execute($outfile, 'person', 'people');
// Which will write the output to an XML file with a parent node of 'people' and each
// record as a child node of 'person'
```

The available decoupled components are 

- Query
    - select fields from the source where a field in that source meets a condition, and a value for that condition. Execute takes an optional outfile (that implements DataInterface), and an optional node name and parent node name for XML writing - the preferred way to use this is to write to a file as it allows you to chunk through massive files.

- Merger
    - Specify multiple source files and merge these together based on an index, for instance email, again like query the execute provides the same options for writing to an outfile

- Split
    - Split a source into multiple outfiles or a multi-plex array - this can be done horizontally specifying the amount of elements per file, or vertically specifying the fields for each file. These can be duplicate fields across the multiple out files when using vertical manipulation.

- Statistics
    - Generate statistics based on an input source.

CSVFile and XMLFile are examples of files that implement DataInterface and DataFile is an abstract class.


The primary Front Controller component, if you'd like to use that is the Manipulator object, this currently only allows you to inject the Data source, Query and Statistics during instantiation.

## Contributing
1. Fork it!
2. Create your feature branch: `git checkout -b my-new-feature`
3. Write some unit tests: 'code!'
4. Commit your changes: `git commit -am 'Add some feature'`
5. Push to the branch: `git push origin my-new-feature`
6. Submit a pull request :D

## History
10/08/2016 Completed README and finished XML support 

## Credits
Get your name here!

## License
MIT