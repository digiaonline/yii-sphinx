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
class SphinxDataProvider extends \CActiveDataProvider
{
    /**
     * @var string the current index to perform the search on.
     */
    private $_index;

    /**
     * @inheritdoc
     */
    public function __construct($modelClass, $config = array())
    {
        parent::__construct($modelClass, $config);

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
            $pagination->applyLimit($criteria);
        }

        /** @var \CSort $sort */
        if (($sort = $this->getSort()) !== false) {
            foreach ($sort->getDirections() as $attributeName => $order) {
                $criteria->applyOrder($attributeName, ($order === \CSort::SORT_ASC) ? 'asc' : 'desc');
            }
        }

        $result = \Yii::app()->sphinx->query($criteria, $this->_index);

        // todo: load active records based on result
        // todo: handle facets

        return array();
    }

    /**
     * @inheritdoc
     */
    protected function fetchKeys()
    {
        // TODO: Implement fetchKeys() method.
    }

    /**
     * @inheritdoc
     */
    protected function calculateTotalItemCount()
    {
        // TODO: Implement calculateTotalItemCount() method.
    }
}