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
    protected $source = null;
    protected $timer = null;
    protected $out = null;

    /**
     * Sets the data source
     *
     * @param DataInterface $source The data source for the processor
     *
     * @return Runner
    **/
    public function from(DataInterface $source)
    {
        $this->source = $source;
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
        $this->out = $out;
        return $this;
    }
    /**
     * Switches on a timer for the execution process
     *
     * @return Runner
    **/
    public function timer()
    {
        $this->timer = new Stopwatch();
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
