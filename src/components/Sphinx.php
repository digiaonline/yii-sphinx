<?php
/**
 * Sphinx class file.
 * @author Christoffer Lindqvist <christoffer.lindqvist@nordsoftware.com>
 * @copyright Copyright &copy; Nord Software 2014-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package nordsoftware.yii-sphinx.components
 */

namespace nordsoftware\yii_sphinx\components;

use FSphinx\FSphinxClient;

/**
 * Sphinx application component that serves as a communication layer between the app and the sphinx api.
 */
class Sphinx extends \CApplicationComponent
{
    /**
     * @var string the default server host for sphinx client.
     * @see http://sphinxsearch.com/docs/current.html#api-func-setserver
     */
    public $serverHost = '127.0.0.1';

    /**
     * @var int the default server port for sphinx client.
     * @see http://sphinxsearch.com/docs/current.html#api-func-setserver
     */
    public $serverPort = 9312;

    /**
     * Default sphinx match mode.
     * @var int
     * @see http://sphinxsearch.com/docs/current.html#matching-modes
     */
    public $matchMode = FSphinxClient::SPH_MATCH_EXTENDED2;

    /**
     * Default sphinx ranking mode.
     * @var int
     * @see http://sphinxsearch.com/docs/current.html#weighting
     */
    public $rankMode = FSphinxClient::SPH_RANK_PROXIMITY_BM25;

    /**
     * Default sphinx sort mode.
     * @var int
     * @see http://sphinxsearch.com/docs/current.html#sorting-modes
     */
    public $sortMode = FSphinxClient::SPH_SORT_RELEVANCE;

    /**
     * @var int the default function applied to the attribute value in order to compute group-by key.
     * @see http://sphinxsearch.com/docs/current.html#api-func-setgroupby
     */
    public $groupFunc = FSphinxClient::SPH_GROUPBY_DAY;

    /**
     * @var int the default clause that controls how the groups will be sorted.
     * @see http://sphinxsearch.com/docs/current.html#api-func-setgroupby
     */
    public $groupSort = '@group desc';

    /**
     * @var string
     * @see http://sphinxsearch.com/docs/current.html#api-func-setsortmode
     */
    public $sortBy = '';

    /**
     * @var array configured indices for the application.
     */
    public $indices = array();

    /**
     * @var \FSphinx\FSphinxClient|null runtime cache for the sphinx api client.
     */
    private $_client;

    /**
     * Getter for the sphinx api client instance.
     * @return FSphinxClient the client.
     */
    public function getClient()
    {
        if ($this->_client === null) {
            $this->_client = new FSphinxClient();
            $this->_client->setServer($this->serverHost, $this->serverPort);
            $this->_client->setMatchMode($this->matchMode);
            $this->_client->setSortMode($this->sortMode, $this->sortBy);
            $this->_client->setRankingMode($this->rankMode);
            $this->_client->setGroupBy('', $this->groupFunc, $this->groupSort);
        }
        return $this->_client;
    }

    /**
     * Runs a query against sphinx and returns the result.
     * @param SphinxCriteria $criteria the criteria for the search.
     * @param string $index the index to search on.
     * @param boolean $resetClient if the sphinx client is to be reset before searching, defaults to true.
     */
    public function query(SphinxCriteria $criteria, $index, $resetClient = true)
    {
        if ($resetClient) {
            $this->_client = null;
        }
        $this->applyCriteria($criteria, $index);
        $results = $this->execute();
        return $results[0];
    }

    /**
     * Runs queries defined in client against sphinx and return result.
     * @return array the raw result from sphinx.
     * @throws \CException if an error occurred.
     */
    protected function execute()
    {
        $client = $this->getClient();

        $results = $client->runQueries();
        if ($error = $client->getLastError()) {
            throw new \CException($error);
        }
        if ($error = $client->getLastWarning()) {
            throw new \CException($error);
        }
        if (!is_array($results)) {
            throw new \CException('Sphinx client returned a non-array.');
        }

        return $results;
    }

    /**
     * Applies the search criteria on the sphinx client.
     * @param SphinxCriteria $criteria the criteria to apply.
     * @param string $index the sphinx index to serach on.
     */
    protected function applyCriteria(SphinxCriteria $criteria, $index)
    {
        $client = $this->getClient();

        if (is_int($criteria->matchMode)) {
            $client->setMatchMode($criteria->matchMode);
        }
        if (is_int($criteria->rankMode)) {
            $client->setRankingMode($criteria->rankMode);
        }
        if (is_int($criteria->sortMode)) {
            $client->setSortMode($criteria->sortMode, $criteria->order);
        }

        if (strlen($criteria->select) > 0) {
            $client->setSelect($criteria->select);
        }

        if ((int)$criteria->limit > 0) {
            $client->setLimits(
                $criteria->offset,
                $criteria->limit,
                $criteria->maxMatches,
                $criteria->cutOff
            );
        }

        if (strlen($criteria->group) > 0) {
            $client->setGroupBy($criteria->group, $criteria->groupFunc);
        }

        if (is_int($criteria->idMax) && is_int($criteria->idMin)) {
            $client->setIDRange(
                $criteria->idMin,
                $criteria->idMax
            );
        }

        $this->getClient()->setFieldWeights($criteria->getFieldWeights());
        $this->getClient()->setIndexWeights($criteria->getIndexWeights());

        $this->setFilters($criteria->getInConditions());
        $this->setFilters($criteria->getNotInConditions(), true);

        $this->setFilterRanges($criteria->getInRanges());
        $this->setFilterRanges($criteria->getNotInRanges(), true);

        $client->addQuery($criteria->getQuery(), $index);
    }

    /**
     * Helper method to add filters to the sphinx client.
     * @param array $filters the filters to add.
     * @param bool $exclude if the filter is include or exclude.
     */
    protected function setFilters(array $filters, $exclude = false)
    {
        $client = $this->getClient();
        foreach ($filters as $field => $values) {
            $client->setFilter($field, $values, $exclude);
        }
    }

    /**
     * Helper method to add filter ranges to the sphinx client.
     * @param array $ranges the ranges to add.
     * @param bool $exclude if the ranges are include or exclude.
     */
    protected function setFilterRanges(array $ranges, $exclude = false)
    {
        $client = $this->getClient();
        foreach ($ranges as $field => $range) {
            if (is_float($range['max']) || is_float($range['min'])) {
                $client->setFilterFloatRange($field, (float)$range['min'], (float)$range['max'], $exclude);
            } else {
                $client->setFilterRange($field, (int)$range['min'], (int)$range['max'], $exclude);
            }
        }
    }
}