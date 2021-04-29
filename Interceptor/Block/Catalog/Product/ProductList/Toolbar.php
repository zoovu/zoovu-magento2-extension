<?php

namespace Semknox\Productsearch\Interceptor\Block\Catalog\Product\ProductList;

use Semknox\Productsearch\Helper\SxHelper;

class Toolbar
{

    public function __construct(
        SxHelper $sxHelper
    ) {
        $this->_sxHelper = $sxHelper;
        $this->_isSxSearch = $sxHelper->isSearch() && $sxHelper->isSxSearchFrontendActive();

        $this->_collection = null;
    }
    
    private function _isNotSxSearch(\Magento\Catalog\Block\Product\ProductList\Toolbar $parent)
    {
        $this->_collection = $parent->getCollection();

        return !$this->_isSxSearch || !is_object($this->_collection) || !isset($this->_collection->_isSxSearch) || !$this->_collection->_isSxSearch;
    }


    /**
     * Retrieve available Order fields list
     *
     * @return array
     */
    public function afterGetAvailableOrders(\Magento\Catalog\Block\Product\ProductList\Toolbar $parent, $result)
    {
        if($this->_isNotSxSearch($parent)) return $result;

        $availableOrders = $this->_collection->_sxAvailableOrders;
        $availableOrders['position'] =__('Position');

        return $availableOrders;           
    }


    /**
     * Set Available order fields list
     *
     * @param array $orders
     * @return $this
     */
    public function afterSetAvailableOrders(\Magento\Catalog\Block\Product\ProductList\Toolbar $parent, $result, $orders)
    {
        if ($this->_isNotSxSearch($parent)) return $parent;

        $parent->_sxAvailableOrder = $orders;
        return $parent;
    }

    /**
     * Return last page number.
     *
     * @return int
     */
    public function afterGetLastPageNum(\Magento\Catalog\Block\Product\ProductList\Toolbar $parent, $result)
    {
        if ($this->_isNotSxSearch($parent)) return $result;

        return (int) $this->_collection->_sxResultsCount / $parent->getLimit();
    }

    /**
     * Total number of products in current category.
     *
     * @return int
     */
    public function afterGetTotalNum(\Magento\Catalog\Block\Product\ProductList\Toolbar $parent, $result)
    {
        if ($this->_isNotSxSearch($parent)) return $result;

        return (int) $this->_collection->_sxResultsCount;
    }

    public function afterGetFirstNum(\Magento\Catalog\Block\Product\ProductList\Toolbar $parent, $result)
    {
        if ($this->_isNotSxSearch($parent)) return $result;

        $currenPageNum = $parent->getCurrentPage() - 1;

        return ($currenPageNum * $parent->getLimit()) + 1;
    }

    public function afterGetLastNum(\Magento\Catalog\Block\Product\ProductList\Toolbar $parent, $result)
    {
        if ($this->_isNotSxSearch($parent)) return $result;

        $calcLastNum = $parent->getFirstNum() + $parent->getLimit() -1;
        return ($calcLastNum < $parent->getTotalNum()) ? $calcLastNum : $parent->getTotalNum();
    }

    public function afterGetPagerHtml(\Magento\Catalog\Block\Product\ProductList\Toolbar $parent, $result)
    {
        if ($this->_isNotSxSearch($parent)) return $result;

        if(!$this->_sxHelper->getSetOrder() || $this->_sxHelper->getSetOrder() == 'position'){
            $result .= '<style>.action.sorter-action{ display: none;}</style>';
        }

        return $result;
    }

    /**
     * Get grid products sort order field
     *
     * @return string
     */
    public function afterGetCurrentOrder(\Magento\Catalog\Block\Product\ProductList\Toolbar $parent, $result)
    {
        if ($this->_isNotSxSearch($parent)) return $result;

        if (!$result || $result == 'relevance') {
            $result = 'position'; 
        }

        $parent->setData('_current_grid_order', $result);
        return $result;

    }

}
