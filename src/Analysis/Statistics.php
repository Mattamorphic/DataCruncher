<?php
namespace mfmbarber\Data_Cruncher\analysis;
use mfmbarber\Data_Cruncher\Config\Validation as Validation;
use mfmbarber\Data_Cruncher\Helpers\DataInterface as DataInterface;
use mfmbarber\Data_Cruncher\Exceptions;

class Statistics
{
    private $_sourceFile;

    public function __construct()
    {
        $this->_type = 'TOTAL';
        $this->_option = null;
    }
    /**
     * Sets the data source for the query
     *
     * @param DataInterface $sourceFile The data source for the query
     *
     * @return Statistics
     **/
    public function fromSource(DataInterface $sourceFile)
    {
        $this->_sourceFile = $sourceFile;
        return $this;
    }
    /**
     * Sets the type of response to percentage rather than totals
     *
     * @return Statistics
    **/
    public function percentages()
    {
        $this->_type = 'PERCENT';
        return $this;
    }
    /**
     * Sets the field to calculate statistics on
     *
     * @param string $field The name of the field to run the statistics on
     *
     * @return Statistics
    **/
    public function setField($field)
    {
        $this->_field = $field;
        return $this;
    }
    /**
     * Sets the _function private property to be a closure, this closure
     * simply returns the value given. Exact implies the key in the results
     * will be exact and not a grouping.
     *
     * @return Statistics
    **/
    public function groupExact()
    {
        $this->_function = function ($value, $option) {
            return $value;
        };
        return $this;
    }
    /**
     * Sets the _function private property to be a closure, this closure
     * returns the numeric grouping given the step for the groups.
     * So for instance a step of 10, would return 0, 10, if the value given
     * was 7
     *
     * @param integer $step The step between each value in the grouping
     *
     * @return Statistics
    **/
    public function groupNumeric($step)
    {
        if (!is_numeric($step)) {
            // TODO : Throw Exception
        }
        $this->_option = $step;
        $this->_function = function ($value, $step) {
            $lower = ((int) ($value / $step)) * $step;
            $upper = (((int) ($value / $step)) + 1) * $step;
            return "$lower, $upper";
        };
        return $this;
    }
    /**
     * Sets the _function private property to be a closure, this closure
     * returns the date grouping given the part of the date to be returned
     * So for instance, given a returnFormat of 'Y', would return 1987 given
     * 24/11/1987 and a dataFormat of d/m/Y
     *
     * @param string $dataFormat   The format that the data is in in the source
     * @param string $returnFormat The format to return the data in
     *
     * @return Statistics
    **/
    public function groupDate($dataFormat, $returnFormat)
    {
        $this->_option = $returnFormat;
        $this->_dataFormat = $dataFormat;
        $this->_function = function ($value, $format) {
            $date = Validation::getDateTime($value, $this->_dataFormat);
            if (!$date) {
                return false;
            }
            return $date->format($format);
        };
        return $this;

    }
    /**
     * Execute the statistics calculation given the parameters are set
     * returns an associative array of key value results
     *
     * @param Helpers\DataInterface $outfile a location to populate with results
     *
     * @return array
    **/
    public function execute(DataInterface $outfile = null)
    {
        // TODO :: Validation of object vars.
        $result = [];
        try {
            $this->_sourceFile->open();
        } catch (Exceptions\FilePointerExistsException $e) {
             // The stream is already open
        }
        if ($outfile !== null) {
            try {
                $outfile->open();
            } catch (Exceptions\FilePointerExistsException $e){
                // The stream is already open
            }
        }
        $rowTotal = 0; // count the rows
        while ([] !== ($row = $this->_sourceFile->getNextDataRow())) {
            $rowTotal++;
            // invoke the closure assigned to the attribute (our statistics func)
            $key = $this->_function
                ->__invoke($row[$this->_field], $this->_option);
            if (false !== $key) {
                if (!array_key_exists($key, $result)) {
                    $result[$key] = 0;
                }
                $result[$key]++;
            }
        }


        if ($this->_type == 'PERCENT') {
            foreach ($result as $key => $value) {
                $result[$key] = (100 / $rowTotal) * $value;
            }
        }
        $this->_sourceFile->close();
        if ($outfile !== null) {
            // write to outfile
            $outfile->writeDataRow(['key', $this->_type]);
            foreach ($result as $key => $value) {
                $row = [
                    'key' => $key,
                    $this->_type => $value
                ];
                $outfile->writeDataRow($row);
            }
            $outfile->close();
            return true;
        }
        return $result;
    }
}
