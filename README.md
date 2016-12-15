[![Build Status](https://travis-ci.org/Matt-Barber/DataCruncher.svg?branch=master)](https://travis-ci.org/Matt-Barber/DataCruncher)
[![Coverage Status](https://coveralls.io/repos/github/Matt-Barber/DataCruncher/badge.svg?branch=master)](https://coveralls.io/github/Matt-Barber/DataCruncher?branch=master)

# DataCruncher :page_facing_up:

##Version : 0.0.5

DataCruncher is a PHP library allowing Database Tables, XML and CSV files (currently) to be queried as you might query a standard database table (with added extras).
This allows you to segment your data efficiently and pipe this to other outsources. There is support as well for returning arrays - but beware this will require loading the array into memory and blarg.

This has been designed around the concept of factories with two main access points to the decoupled components:

```php
<?php
    use mfmbarber\DataCruncher\Helpers\DataSource;
    use mfmbarber\DataCruncher\Processor;

    // This gives us access to the data sources
    $obj = DataSource::generate('file', 'csv');

    // This gives us access to the processors
    $obj = Processor::generate('segmentation', 'query');

```

## Installation
The easiest way to install this is probably to git clone this into an extensions folder in your project, then update your composer.json with an autoloader reference.
It will, once complete, be uploaded to packagist and installed via composer - woo!

## Usage
Using a decoupled component, i.e. the Query object

Our basic includes
```php
<?php
// include your autoloader
include 'vendor/autoload.php';
// a helper class for generating data sources
use mfmbarber\DataCruncher\Helpers\DataSource;

// our processor
use mfmbarber\DataCruncher\Processor;

An example using a CSV input

```php
$query = Processor::generate('segmentation', 'query');
$file = DataSource::generate('file', 'csv');

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

$result === [
    [
        'name' => 'matt',
        'age' => 28
    ],
    [
        'name' => 'tony'
        'age' => 35
    ]
];

```

Example writing to an outfile
```php
$outfile = DataSource::generate('file', 'csv');
$outfile->setSource('example/output.csv', ['modifier' => 'w']);

$result = $query->fromSource($file)
    ->select(['name', 'age'])
    ->where('email')
    ->condition('CONTAINS')
    ->value('gmail')
    ->execute($outfile);
```

Example writing from a CSV query to an XML file

```php
$outfile = DataSource::generate('file', 'xml', 'person', 'people');
$outfile->setSource('example/output.xml', ['modifier' => 'w']);

$result = $query->fromSource($file)
    ->select(['name', 'age'])
    ->where('email')
    ->condition('CONTAINS')
    ->value('gmail')
    ->execute($outfile);
```

Example using a Database table as a source
Outputting to a CSV

```php
// Say we wanted to use a database, and write to a CSV we could use
$db = DataSource::generate('db', 'sql');
$db->setSource('TEST', [
    'username' => 'root',
    'password' => '',
    'table' => 'users'
]);

$outfile = DataSource::generate('file', 'csv');
$outfile->setSource('./example/outfile3.csv', ['modifier' => 'w']);

$query = Processor::generate('segmentation', 'query');

$result = $query->fromSource($db)
    ->select(['firstname', 'lastname'])
    ->where('email')
    ->condition('CONTAINS')
    ->value('gmail.com')
    ->execute($outfile);
```

Queries support limiting the amount of results

```php

$result = $query->fromSource($db)
    ->select(['firstname', 'lastname'])
    ->where('email')
    ->condition('CONTAINS')
    ->value('gmail.com')
    ->limit(10)
    ->execute($outfile);
```

Tracking execution time and memory

```php
-> execute($outfile, null , true);
// which returns a ['data' => ..., 'timer' => ['elapsed' => 0, 'memory' => 0]] structure

```

The available decoupled components under the processor are:

- Query
    - select fields from the source where a field in that source meets a condition, and a value for that condition. Execute takes an optional outfile (that implements DataInterface), and an optional node name and parent node name for XML writing - the preferred way to use this is to write to a file as it allows you to chunk through massive files.
    ```php
        $obj = Processor::generate('segmentation', 'query');
    ```

- Merger
    - Specify multiple source files and merge these together based on an index, for instance email, again like query the execute provides the same options for writing to an outfile
    ```php
        $obj = Processor::generate('segmentation', 'merge');
    ```

- Split
    - Split a source into multiple outfiles or a multi-plex array - this can be done horizontally specifying the amount of elements per file, or vertically specifying the fields for each file. These can be duplicate fields across the multiple out files when using vertical manipulation.
    ```php
        $obj = Processor::generate('segmentation', 'split');
    ```

- Statistics
    - Generate statistics based on an input source.
    ```php
        $obj = Processor::generate('analysis', 'statistics');
    ```

    - With Rules
    ```php
        $obj = Processor::generate('analysis', 'rule');
    ```

The available decoupled components under the data sources  are:
    - CSV
    ```php
        $obj = DataSource::generate('file', 'csv');
    ```
    - XML
    ```php
        $obj = DataSource::generate('file', 'xml', 'person', people);
    ```
    - Database
    ```php
        $obj = DataSource::generate('db', 'sql');
    ```
    - System
    ```php
        $obj = DataSource::generate('system', 'csv');
    ```

The primary Front Controller component, if you'd like to use that is the Manipulator object, this currently only allows you to inject the Data source, Query and Statistics during instantiation.

## Tests

Tests can be run on most base systems using the PHPUnit xml file, simply call phpunit from the top level directory. For setup of folders see the same commands run for travis in .travis.yml

## Issues?

Please raise issues through the github :octocat: issues system, I'm always looking to improve this library so any feedback is always welcome! :smile:

## Contributing
1. Fork it!
2. Create your feature branch: `git checkout -b my-new-feature`
3. Write some unit/integration tests: 'code!'
4. Commit your changes: `git commit -am 'Add some feature'`
5. Push to the branch: `git push origin my-new-feature`
6. Submit a pull request :D

## Upcoming Release 0.0.5
- Factory Processor
- Removed Manipulator
- Rounding percentages when calculating stats
- Codeclimate integration
- Removed unused variables
- Wildcard Selects on queries
- Changed method on to using in Merger (short method infringement)

## History
03/12/2016
- Refactored to use PHP Generators
- Re-written integration testing
- Renamed all Unit Tests to standardise
- Changed rule array result to object
- Added CSV File integer sorting
- Increased test coverage
- Initial CSV system handler (for strings)

13/11/2016
- Added Database Table support
- Added Database to Factory
- CSV Sorting (On \*nix systems)
- CSV Validation
- Updated CSV Get Headers
- Added PHPUnit speedtrap support
- Updated namespacing (to camel case)


12/10/2016
- Factory DataSources
- Regex Support
- Rule abstraction for statistics
- PHP7 only
- Chunked read write to increase performance
- General optimisations
- Improved test coverage
- Execution timer support

10/08/2016
- Completed README and finished XML support

## Credits
Get your name here!

## License
MIT
