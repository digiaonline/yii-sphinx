<?php
/**
 * SphinxDataProvider class file.
 * @author Christoffer Lindqvist <christoffer.lindqvist@nordsoftware.com>
 * @copyright Copyright &copy; Nord Software 2014-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package nordsoftware.yii-sphinx.components
 */

namespace nordsoftware\yii_sphinx\components;

/**
 * Data provider for sphinx searches.
 *
 * The provider finds active records based on the search result from sphinx.
 * The provider also supports facets that are stored separately from the search result.
 */
class SphinxDataProvider extends \CDataProvider
{
    /**
     * @var string the active record class name. The {@link getData()} method will return objects of this class.
     */
    public $modelClass;

    /**
     * @var \CActiveRecord the active record finder instance.
     */
    public $model;

    /**
     * @var string the name of key attribute for {@link modelClass}. If not set, the primary key will be used.
     */
    public $keyAttribute = null;

    /**
     * @var string runtime cache for search index.
     */
    private $_index;

    /**
     * @var SphinxCriteria runtime cache for search criteria.
     */
    private $_criteria;

    /**
     * @inheritdoc
     */
    public function __construct($modelClass, $config = array())
    {
        if (is_string($modelClass)) {
            $this->modelClass = $modelClass;
            $this->model = \CActiveRecord::model($this->modelClass);
        } elseif ($modelClass instanceof \CActiveRecord) {
            $this->modelClass = get_class($modelClass);
            $this->model = $modelClass;
        }
        $this->setId($this->modelClass);
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }

        $index = strtolower($this->modelClass);
        $indices = \Yii::app()->sphinx->indices;
        if (!in_array($index, $indices)) {
            throw new \CException(sprintf('Model "%s" has no index "%s" defined.', $this->modelClass, $index));
        }
        $this->_index = $index;
    }

    /**
     * @inheritdoc
     */
    protected function fetchData()
    {
        $criteria = clone $this->getCriteria();

        if (($pagination = $this->getPagination()) !== false) {
            $pagination->setItemCount($this->getTotalItemCount());
            $criteria->limit = $pagination->getLimit();
            $criteria->offset = $pagination->getOffset();
        }

        if (($sort = $this->getSort()) !== false) {
            foreach ($sort->getDirections() as $attribute => $order) {
                $mode = ($order === \CSort::SORT_ASC) ? 'asc' : 'desc';
                $criteria->order = empty($criteria->order) ? "{$attribute} {$mode}" : ", {$attribute} {$mode}";
            }
        }

        $result = \Yii::app()->sphinx->query($criteria, $this->_index);
        $data = array();

        if (!empty($result['matches'])) {
            $modelIds = array();
            foreach ($result['matches'] as $modelId => $match) {
                $modelIds[] = $modelId;
            }
            $criteria = new \CDbCriteria;
            $criteria->order = 'FIELD(id, ' . implode(',', $modelIds) . ')';
            $data = $this->model->findAllByPk($modelIds, $criteria);
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    protected function fetchKeys()
    {
        $keys = array();
        foreach ($this->getData() as $i => $data) {
            if (!($data instanceof \CActiveRecord)) {
                throw new \CException('Provider contains data of invalid type.');
            }
            $key = $this->keyAttribute === null ? $data->getPrimaryKey() : $data->{$this->keyAttribute};
            $keys[$i] = is_array($key) ? implode(',', $key) : $key;
        }
        return $keys;
    }

    /**
     * @inheritdoc
     */
    protected function calculateTotalItemCount()
    {
        $result = \Yii::app()->sphinx->query($this->getCriteria(), $this->_index);
        if (!isset($result['total_found'])) {
            throw new \CException(sprintf('Sphinx did not return a total found item count.'));
        }
        return (int)$result['total_found'];
    }

    /**
     * Getter for the search criteria.
     * @return SphinxCriteria
     */
    protected function getCriteria()
    {
        if ($this->_criteria !== null) {
            return $this->_criteria;
        }
        return $this->_criteria = new SphinxCriteria();
    }

    /**
     * Setter for the search criteria.
     * @param mixed
     */
    public function setCriteria($value)
    {
        $this->_criteria = ($value instanceof SphinxCriteria) ? $value : new SphinxCriteria($value);
    }
}