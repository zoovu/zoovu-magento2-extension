<?php

namespace Semknox\Productsearch\Block\LayeredNavigation\Navigation;

use Semknox\Productsearch\Helper\SxHelper;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Semknox\Productsearch\Block\LayeredNavigation\Navigation\FilterItemAdapterFactory;



class FilterRenderer extends \Magento\LayeredNavigation\Block\Navigation\FilterRenderer implements FilterInterface
{

    public $isActiveFilter = false;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = [],
        SxHelper $sxHelper,
        FilterItemAdapterFactory $filterItemAdapterFactory
    )
    {
        $this->_sxHelper = $sxHelper;
        $this->_filterItemAdapter = $filterItemAdapterFactory;
        parent::__construct($context, $data);
    }

    public function setSxFilter($sxFilter)
    {
        $this->_sxFilter = $sxFilter;
    }


    /**
     * @param FilterInterface $filter
     * @return string
     */
    public function render(FilterInterface $filter)
    {
        //$filterList = $this->_sxHelper->getSxResponseStore('filterList');
        // ... filter  options of ONE Filter
        
        $this->assign('filterItems', $filter->getItems());
        $html = $this->_toHtml();
        $this->assign('filterItems', []);
        return $html;
    }


    /**
     * Set request variable name which is used for apply filter
     *
     * @param   string $varName
     * @return  \Magento\Catalog\Model\Layer\Filter\FilterInterface
     */
    public function setRequestVar($varName)
    {
        $this->requestVar = $varName;
    }

    /**
     * Get request variable name which is used for apply filter
     *
     * @return string
     */
    public function getRequestVar()
    {
        return 'sx_' . \urlencode($this->getName());
    }

    /**
     * Get filter value for reset current filter state
     *
     * @return mixed
     */
    public function getResetValue()
    {
        return '';
    }

    /**
     * Retrieve filter value for Clear All Items filter state
     *
     * @return mixed
     */
    public function getCleanValue()
    {
        return '';
    }

    /**
     * Apply filter to collection
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return $this
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        return $this;
    }

    /**
     * Get filter items count
     *
     * @return int
     */
    public function getItemsCount()
    {
        return count($this->_sxFilter->getOptions());
    }

    /**
     * Get all filter items
     *
     * @return array
     */
    public function getItems()
    {
        $items = [];

        foreach($this->_sxFilter->getOptions() as $option){
            $item = $this->_filterItemAdapter->create(['data' => ['filter' => $this]]);
            $item->setSxOption($option);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Set all filter items
     *
     * @param array $items
     * @return $this
     */
    public function setItems(array $items)
    {
        return '';
    }

    /**
     * Retrieve layer object
     *
     * @return \Magento\Catalog\Model\Layer
     */
    public function getLayer()
    {
        return false;
    }

    /**
     * Set attribute model to filter
     *
     * @param   \Magento\Eav\Model\Entity\Attribute $attribute
     * @return  \Magento\Catalog\Model\Layer\Filter\FilterInterface
     */
    public function setAttributeModel($attribute)
    {
        return false;
    }

    /**
     * Get attribute model associated with filter
     *
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttributeModel()
    {
        return false;
    }

    /**
     * Get filter text label
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getLabel()
    {
        return $this->isActiveFilter ? $this->getActiveValue() : $this->_sxFilter->getName();
    }
    
    public function getName()
    {
        return is_array($this->_sxFilter) ? $this->_sxFilter['name'] : $this->getLabel();
    }

    public function getActiveValue()
    {
        return is_array($this->_sxFilter) ? $this->_sxFilter['value']['name'] : '';
    }

    /**
     * Retrieve current store id scope
     *
     * @return int
     */
    public function getStoreId()
    {
        return 0;
    }

    /**
     * Set store id scope
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        return $this;
    }

    /**
     * Retrieve Website ID scope
     *
     * @return int
     */
    public function getWebsiteId()
    {
        return 0;
    }

    /**
     * Set Website ID scope
     *
     * @param int $websiteId
     * @return $this
     */
    public function setWebsiteId($websiteId)
    {
        return $this;
    }

    /**
     * Clear current element link text, for example 'Clear Price'
     *
     * @return false|string
     */
    public function getClearLinkText()
    {
        return 'clear';
    }

}
