<?php

namespace Semknox\Productsearch\Block\LayeredNavigation\Navigation;

use Semknox\Productsearch\Helper\SxHelper;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Semknox\Productsearch\Block\LayeredNavigation\Navigation\FilterItemAdapterFactory;


class FilterRenderer extends \Magento\LayeredNavigation\Block\Navigation\FilterRenderer implements FilterInterface
{

    public $isActiveFilter = false;
    public $activeFilterList = [];

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        SxHelper $sxHelper,
        FilterItemAdapterFactory $filterItemAdapterFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    )
    {
        $this->_sxHelper = $sxHelper;
        $this->_filterItemAdapter = $filterItemAdapterFactory;
        parent::__construct($context, $data);
    }


    /**
     * @param FilterInterface $filter
     * @return string
     */
    public function render(FilterInterface $filter)
    {
        // if range.. do nouislider
        $rangeFilter = $this->_sxHelper->getSxResponseStore('rangeFilter', []);
        if(isset($rangeFilter[$filter->getName()])){

            $sxFilter = $rangeFilter[$filter->getName()];

            return "<div class='slider-wrapper'>
                        <div class='slider sxRangeFilter' id='sx_". $filter->getName(). "' 
                            data-start='". $sxFilter->getActiveMin(). "'
                            data-end='" . $sxFilter->getActiveMax() . "'
                            data-range-min='" . $sxFilter->getMin() . "'
                            data-range-max='" . $sxFilter->getMax() . "'
                            data-url='". $filter->getRemoveUrl(). "'
                        ></div>
                        <div class='slider-helper'>
                            <input class='start' value='' min='' type='number' name='num1'>
                            <span>-</span>
                            <input class='end' value='' max='' type='number' name='num2'>
                            <span class='unit'>" . $sxFilter->getUnit() . "</span>
                            <button class='' type='button'><i class='fa fa-angle-right'></i></button>
                        </div>
                    </div>";

        } else {
            $this->assign('filterItems', $filter->getItems());
            $html = $this->_toHtml();
            $this->assign('filterItems', []);
            return $html;
        }

    }



    public function setSxFilter($sxFilter)
    {
        $this->_sxFilter = $sxFilter;
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
        return $items;
    }

    /**
     * Retrieve layer object
     *
     * @return \Magento\Catalog\Model\Layer
     */
    public function getLayer()
    {
        return parent::getLayer();
    }

    /**
     * Set attribute model to filter
     *
     * @param   \Magento\Eav\Model\Entity\Attribute $attribute
     * @return  \Magento\Catalog\Model\Layer\Filter\FilterInterface
     */
    public function setAttributeModel($attribute)
    {
        return parent::setAttributeModel($attribute);
    }

    /**
     * Get attribute model associated with filter
     *
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttributeModel()
    {
        return parent::getAttributeModel();
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
        if(is_array($this->_sxFilter)){
            return isset($this->_sxFilter['value']['name']) ? $this->_sxFilter['value']['name'] : $this->_sxFilter['value']['value'];
        }

        return '';
    }

    /**
     * Retrieve current store id scope
     *
     * @return int
     */
    public function getStoreId()
    {
        return parent::getStoreId();
    }

    /**
     * Set store id scope
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        return parent::setStoreId($storeId);
    }

    /**
     * Retrieve Website ID scope
     *
     * @return int
     */
    public function getWebsiteId()
    {
        return parent::getWebsiteId();
    }

    /**
     * Set Website ID scope
     *
     * @param int $websiteId
     * @return $this
     */
    public function setWebsiteId($websiteId)
    {
        return parent::setWebsiteId($websiteId);
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


    public function getActiveFilters()
    {
        return $this->activeFilterList;
    }


    /**
     *
     * @return false|string
     */
    public function getRemoveUrl()
    {
        $filterState = [];

        foreach ($this->getActiveFilters() as $item) {
            $filterState[$item->getRequestVar()] = $item->getActiveValue();
        }

        // remove this filter
        $filterState[$this->getRequestVar()] = $this->getCleanValue();

        $params['_current'] = true;
        $params['_use_rewrite'] = true;
        $params['_query'] = $filterState;
        $params['_escape'] = true;

        return $this->_urlBuilder->getUrl('*/*/*', $params);

    }

}
