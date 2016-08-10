<?php
namespace mfmbarber\Data_Cruncher;

class Manipulator
{
    private $_dataSource = null;
    private $_statistics = null;
    private $_query = null;
    /**
     * Front controller gives access to sub functionality, using dependancy
     * injection
     *
     * @param Helpers\DataInterface $dataSource The source to manipulate
     * @param Segmentation\Query    $query      Access to the query funcs
     * @param Analysis\Statistics   $statistics Access to the statistics funcs
     *
     * @return Manipulator
    **/
    public function __construct(
        Helpers\DataInterface $dataSource,
        Segmentation\Query $query = null,
        Analysis\Statistics $statistics = null
    ) {
        $this->_query = $query;
        $this->_statistics = $statistics;
        $this->_dataSource = $dataSource;
    }
    /**
     * Configures the dataSource to use
     *
     * @param string $location    Where to get the seperated value data from
     * @param array  $properties The properties to set on the datasource
     *
     * @return null
    **/
    public function setDataSource($location, array $properties)
    {
        $this->_dataSource->setSource($location, $properties);
        if (isset($this->_query)) {
            $this->_query->fromSource($this->_dataSource);
        }
        if (isset($this->_statistics)) {
            $this->_statistics->fromSource($this->_dataSource);
        }
    }

    /**
     * Returns the location of the data source
     *
     * @return string
    **/
    public function getDataSourceLocation()
    {
        return $this->_dataSource->getSourceName();
    }

    /**
     * Returns the statistics object (exposed methods and behaviour)
     *
     * @return Analysis\Statistics
    **/
    public function statistics()
    {
        if ($this->_statistics !== null) {
            return $this->_statistics;
        } else {
            throw new Exceptions\AttributeNotSetException(
                'Statistics object not passed during instantiation'
            );
        }
    }

    /**
     * Returns the query object (exposed methods and behaviour)
     *
     * @return Segmentation\Query
    **/
    public function query()
    {
        if ($this->_query !== null) {
            return $this->_query;
        } else {
            throw new Exceptions\AttributeNotSetException(
                'Query object not passed during instantiation'
            );
        }
    }
}
