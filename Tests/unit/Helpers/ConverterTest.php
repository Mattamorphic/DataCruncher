<?php

// namespace mfmbarber\Data_Cruncher\Tests\Unit\Helpers;
// use mfmbarber\Data_Cruncher\Tests\Unit\Mocks as Mocks;
// use mfmbarber\Data_Cruncher\Helpers\Converter as Converter;
//
// class ConverterTest extends \PHPUnit_Framework_TestCase
// {
//     public function executeConvertToJsonWorksCorrectly()
//     {
//         $data = "email, name, colour, dob, age\n"
//                ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
//                ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
//                ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
//                ."no_name@something.com, , \"green\", 01/01/2000, fifteen";
//         $customMocks = new Mocks();
//         $sourceFile = $customMocks->createMockSourceFile('mfmbarber\Data_Cruncher\Helpers\CSVFile', $data);
//
//         $expected = '
//             [
//                 {
//                     "email": "mfmbarber@test.com",
//                     "name": "matt",
//                     "colour": [
//                         "black",
//                         "green",
//                         "blue"
//                     ],
//                     "dob": "24/11/1987",
//                     "age": "28"
//                 },
//                 {
//                     "email": "matt.barber@test.com",
//                     "name": "matthew",
//                     "colour": [
//                         "red",
//                         "green"
//                     ],
//                     "dob": "01/12/1980",
//                     "age": "35"
//                 },
//                 {
//                     "email": "tony.stark@avengers.com",
//                     "name": "matt",
//                     "colour": [
//                         "black",
//                         "green",
//                         "blue"
//                     ],
//                     "dob": "24/11/1987",
//                     "age": "28"
//                 },
//                 {
//                     "email": "mfmbarber@test.com",
//                     "name": "matt",
//                     "colour": [
//                         "black",
//                         "green",
//                         "blue"
//                     ],
//                     "dob": "24/11/1987",
//                     "age": "28"
//                 }
//             ]';
//
//         $convert = new Converter();
//         $result = $convert->fromSource($sourceFile)
//             ->toJson()
//             ->execute();
//         $this->assertEquals(
//             $result,
//             $expected,
//             'Convertion doesn\'t match expected JSON'
//         );
//     }
// }
// require_once 'tests/Mocks.php';
// class ConverterTest extends PHPUnit_Framework_TestCase
// {
//     public function executeConvertToJsonWorksCorrectly()
//     {
//         $data = "email, name, colour, dob, age\n"
//         ."mfmbarber@test.com, matt, \"black, green, blue\", 24/11/1987, 28\n"
//         ."matt.barber@test.com, matthew, \"red, green\", 01/12/1980, 35\n"
//         ."tony.stark@avengers.com, tony, \"red, gold\", 02/05/1990, 25\n"
//         ."no_name@something.com, , \"green\", 01/01/2000, fifteen";
//         $customMocks = new Mocks();
//         $sourceFile = $customMocks->createMockSourceFile('mfmbarber\Data_Cruncher\Helpers\CSVFile', $data);
//         $converter = new mfmbarber\Data_Cruncher\Helpers\Converter();
//
//         $result = $convert->fromSource($sourceFile)
//             ->toJson()
//             ->execute();
//
//         $this->assertEquals(
//
//         );
//     }
//     public function executeConvertToXmlWorksCorrectly()
//     {
//
//     }
//
// }
