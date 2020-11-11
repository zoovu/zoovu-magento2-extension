<?php

namespace Semknox\Productsearch\Block\Catalog\Product\ProductList;

use Magento\Catalog\Block\Product\ProductList\Toolbar as CatalogSearchToolbar;
use Magento\Catalog\Helper\Product\ProductList;
use Magento\Catalog\Model\Product\ProductList\Toolbar as ToolbarModel;
use Magento\Catalog\Model\Product\ProductList\ToolbarMemorizer;

use Semknox\Productsearch\Helper\SxHelper;

class Toolbar extends CatalogSearchToolbar
{

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Catalog\Model\Config $catalogConfig,
        ToolbarModel $toolbarModel,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        ProductList $productListHelper,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        array $data = [],
        ToolbarMemorizer $toolbarMemorizer = null,
        \Magento\Framework\App\Http\Context $httpContext = null,
        \Magento\Framework\Data\Form\FormKey $formKey = null,
        \Magento\Framework\App\Request\Http $request,
        SxHelper $sxHelper
    ) {
        $this->_sxHelper = $sxHelper;
        $this->_isSxSearch = ($request->getFullActionName() == 'catalogsearch_result_index') && $this->_sxHelper->isSxSearchFrontendActive();
        parent::__construct($context, $catalogSession, $catalogConfig, $toolbarModel, $urlEncoder, $productListHelper, $postDataHelper, $data, $toolbarMemorizer, $httpContext, $formKey);
    }

    /**
     * Set collection to pager
     *
     * @param \Magento\Framework\Data\Collection $collection
     * @return $this
     */
    public function setCollection($collection)
    {
        if(!$this->_isSxSearch || (isset($this->_collection->_isSxSearch) && !$this->_collection->_isSxSearch)) return parent::setCollection($collection);

        $this->_collection = $collection;

        return $this;
    }



    /**
     * Retrieve available Order fields list
     *
     * @return array
     */
    public function getAvailableOrders()
    {
        if(!$this->_isSxSearch || !$this->_collection->_isSxSearch) return parent::getAvailableOrders();

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
    public function setAvailableOrders($orders)
    {
        if (!$this->_isSxSearch) return parent::setAvailableOrders($orders);

        $this->_sxAvailableOrder = $orders;
        return $this;
    }

    /**
     * Return last page number.
     *
     * @return int
     */
    public function getLastPageNum()
    {
        if (!$this->_isSxSearch || !$this->_collection->_isSxSearch) return parent::getLastPageNum();

        return (int) $this->_collection->_sxResultsCount / $this->getLimit();
    }

    /**
     * Total number of products in current category.
     *
     * @return int
     */
    public function getTotalNum()
    {
        if (!$this->_isSxSearch || !$this->_collection->_isSxSearch) return parent::getTotalNum();

        return (int) $this->_collection->_sxResultsCount;
    }


}
