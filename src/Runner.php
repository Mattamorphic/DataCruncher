<?php
/**
 * Runner
 *
 * @package DataCruncher
 * @author matt barber <mfmbarber@gmail.com>
 *
 * This is the abstract concept of a runner - each processor is a runner
 * This therefore implements common methods these might use such as the source,
 * the out, and a timer.
 *
 */

namespace mfmbarber\DataCruncher;

use Symfony\Component\Stopwatch\Stopwatch;
use mfmbarber\DataCruncher\Config\Validation as Validation;
use mfmbarber\DataCruncher\Helpers\Interfaces\DataInterface as DataInterface;
use mfmbarber\DataCruncher\Exceptions;

abstract class Runner
{
    protected $_source = null;
    protected $_timer = null;
    protected $_out = null;

    /**
     * Sets the data source
     *
     * @param DataInterface $source The data source for the processor
     *
     * @return Runner
    **/
    public function from(DataInterface $source)
    {
        $this->_source = $source;
        return $this;
    }

    /**
     * Sets the output for the processor
     *
     * @param DataInterface $out  Sets the output data interface
     *
     * @return Runner
    **/
    public function out(DataInterface $out)
    {
        Validation::openDataFile($out, true);
        $this->_out = $out;
        return $this;
    }
    /**
     * Switches on a timer for the execution process
     *
     * @return Runner
    **/
    public function timer()
    {
        $this->_timer = new Stopwatch();
        return $this;
    }

    // TODO :: Add the following
    // public function toArray()
    // {
    //
    // }
    // public function toJson()
    // {
    //
    // }
    // public function toString()
    // {
    //
    // }
}
