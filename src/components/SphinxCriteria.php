<?php
/**
 * SphinxCriteria class file.
 * @author Christoffer Lindqvist <christoffer.lindqvist@nordsoftware.com>
 * @copyright Copyright &copy; Nord Software 2014-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package nordsoftware.yii-sphinx.components
 */

namespace nordsoftware\yii_sphinx\components;

/**
 * Criteria for configuring the sphinx client when performing a search.
 */
class SphinxCriteria extends \CComponent
{
    /**
     * Sphinx match mode.
     * @var int
     * @see http://sphinxsearch.com/docs/current.html#matching-modes
     */
    public $matchMode;

    /**
     * Sphinx ranking mode.
     * @var int
     * @see http://sphinxsearch.com/docs/current.html#weighting
     */
    public $rankMode;

    /**
     * Sphinx sort mode.
     * @var int
     * @see http://sphinxsearch.com/docs/current.html#sorting-modes
     */
    public $sortMode;

    /**
     * Controls how much matches "searchd" will keep in RAM while searching.
     * @var int
     * @see http://sphinxsearch.com/docs/current.html#api-func-setlimits
     */
    public $maxMatches = 0;

    /**
     * Tells "searchd" to forcibly stop search query once {@link $cutOff) matches had been found and processed.
     * @var int
     * @see http://sphinxsearch.com/docs/current.html#api-func-setlimits
     */
    public $cutOff = 0;

    /**
     * @var string
     * @see http://sphinxsearch.com/docs/current.html#api-func-setsortmode
     */
    public $order = '';

    /**
     * @var string select clause, listing specific attributes to fetch.
     * @see http://sphinxsearch.com/docs/current.html#api-func-setselect
     */
    public $select;

    /**
     * @var int sets offset into server-side result set.
     * @see http://sphinxsearch.com/docs/current.html#api-func-setlimits
     */
    public $offset;

    /**
     * @var int amount of matches to return to client starting from that {@link $offset).
     * @see http://sphinxsearch.com/docs/current.html#api-func-setlimits
     */
    public $limit;

    /**
     * @var string contains group-by attribute name.
     * @see http://sphinxsearch.com/docs/current.html#api-func-setgroupby
     */
    public $group;

    /**
     * @var int the default function applied to the attribute value in order to compute group-by key.
     * @see http://sphinxsearch.com/docs/current.html#api-func-setgroupby
     */
    public $groupFunc;

    /**
     * @var int accepted range of document max id.
     * @see http://sphinxsearch.com/docs/current.html#api-func-setidrange
     */
    public $idMax;

    /**
     * @var int accepted range of document min id.
     * @see http://sphinxsearch.com/docs/current.html#api-func-setidrange
     */
    public $idMin;

    /**
     * Only match records where an attribute value is in the given set.
     * @var array
     * @see http://sphinxsearch.com/docs/current.html#api-func-setfilter
     */
    private $_includeFilters = array();

    /**
     * Only match records where an attribute value is not in the given set.
     * @var array
     * @see http://sphinxsearch.com/docs/current.html#api-func-setfilter
     */
    private $_excludeFilters = array();

    /**
     * Binds per-field weights by name.
     * @var array
     * @see http://sphinxsearch.com/docs/current.html#api-func-setfieldweights
     */
    private $_fieldWeights = array();

    /**
     * Sets per-index weights, and enables weighted summing of match weights across different indexes.
     * @var array
     * @see http://sphinxsearch.com/docs/current.html#api-func-setindexweights
     */
    private $_indexWeights = array();

    /**
     * Only documents where attribute value stored in the index is between $min and $max will be matched.
     * @var array
     * @see http://sphinxsearch.com/docs/current.html#api-func-setfilterrange
     * @see http://sphinxsearch.com/docs/current.html#api-func-setfilterfloatrange
     */
    private $_includeFilterRanges = array();

    /**
     * Only documents where attribute value stored in the index is between $min and $max will be rejected.
     * @var array
     * @see http://sphinxsearch.com/docs/current.html#api-func-setfilterrange
     * @see http://sphinxsearch.com/docs/current.html#api-func-setfilterfloatrange
     */
    private $_excludeFilterRanges = array();

    /**
     * @var string runtime cache for the sphinx query string.
     */
    private $_query = '';

    /**
     * Adds include filters for the search.
     * @param string $field name name of the field.
     * @param mixed $values the values for the field.
     */
    public function addInCondition($field, $values)
    {
        $field = strtolower(trim($field));
        $this->_includeFilters[$field] = $values;
    }

    /**
     * Getter for the include filters for the search.
     * @return array the filters.
     */
    public function getInConditions()
    {
        return $this->_includeFilters;
    }

    /**
     * Adds exclude filters for the search.
     * @param string $field name name of the field.
     * @param mixed $values the values for the field.
     */
    public function addNotInCondition($field, $values)
    {
        $field = strtolower(trim($field));
        $this->_excludeFilters[$field] = $values;
    }

    /**
     * Getter for the exclude filters for the search.
     * @return array the filters.
     */
    public function getNotInConditions()
    {
        return $this->_excludeFilters;
    }

    /**
     * Adds a field weight.
     * @param string $field the field name.
     * @param int $weight the integer weight.
     */
    public function addFieldWeight($field, $weight)
    {
        $this->_fieldWeights[(string)$field] = (int)$weight;
    }

    /**
     * Setter for the field weights.
     * @param array $weights assoc array of field names to integer weights.
     */
    public function setFieldWeights(array $weights)
    {
        $this->_fieldWeights = array();
        foreach ($weights as $field => $weight) {
            $this->addFieldWeight($field, $weight);
        }
    }

    /**
     * Getter for the field weights.
     * @return array the weights.
     */
    public function getFieldWeights()
    {
        return $this->_fieldWeights;
    }

    /**
     * Adds a index weight.
     * @param string $index the index name.
     * @param int $weight the integer weight.
     */
    public function addIndexWeight($index, $weight)
    {
        $this->_indexWeights[(string)$index] = (int)$weight;
    }

    /**
     * Setter for the index weights.
     * @param array $weights assoc list of index names to integer weights.
     */
    public function setIndexWeights(array $weights)
    {
        $this->_indexWeights = array();
        foreach ($weights as $index => $weight) {
            $this->addIndexWeight($index, $weight);
        }
    }

    /**
     * Getter for the index weights.
     * @return array the weights.
     */
    public function getIndexWeights()
    {
        return $this->_indexWeights;
    }

    /**
     * Adds a filter in range.
     * @param string $field the field to filter on.
     * @param int $min the min value.
     * @param int $max the max value.
     */
    public function addInRange($field, $min, $max)
    {
        $field = strtolower(trim($field));
        $this->_includeFilterRanges[$field] = array(
            'min' => (int)$min,
            'max' => (int)$max,
        );
    }

    /**
     * Getter for the include filter ranges.
     * @return array the filters.
     */
    public function getInRanges()
    {
        return $this->_includeFilterRanges;
    }

    /**
     * Adds a filter not in range.
     * @param string $field the field to filter on.
     * @param int $min the min value.
     * @param int $max the max value.
     */
    public function addNotInRange($field, $min, $max)
    {
        $field = strtolower(trim($field));
        $this->_excludeFilterRanges[$field] = array(
            "min" => (int)$min,
            "max" => (int)$max,
        );
    }

    /**
     * Getter for the exclude filter ranges.
     * @return array the filters.
     */
    public function getNotInRanges()
    {
        return $this->_excludeFilterRanges;
    }

    /**
     * Setter for the sphinx search query.
     * @param string $query the query.
     */
    public function setQuery($query)
    {
        $this->_query = $query;
    }

    /**
     * Getter for the sphinx search query.
     * @return string the query.
     */
    public function getQuery()
    {
        return $this->_query;
    }
}