<?php

namespace Semknox\Productsearch\Model\Filter;

use Semknox\Productsearch\Helper\SxHelper;


//abstract class AbstractFilter extends \Magento\Framework\DataObject implements \Magento\Catalog\Model\Layer\Filter\FilterInterface
abstract class AbstractFilter extends \Magento\Catalog\Model\Layer\Filter\AbstractFilter implements \Magento\Catalog\Model\Layer\Filter\FilterInterface
{

    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        SxHelper $sxHelper,
        \Magento\Framework\View\Element\Context $context,
        $data
    ) {
        $this->_filterItemFactory = $filterItemFactory;
        $this->_sxHelper = $sxHelper;
        $this->_urlBuilder = $context->getUrlBuilder();

        $this->_data = $data;
        $this->_sxFilter = $this->getData('sxFilter');
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

    protected function _initItems()
    {
        $data = $this->_getItemsData();
        $items = [];
        foreach ($this->_sxFilter->getOptions() as $sxOption) {
            $items[] = $this->_createItem($sxOption->getName(), $sxOption->getValue(), $sxOption->getNumberOfResults());
        }
        $this->_items = $items;
        return $this;
    }


    public function getName()
    {
        return $this->_sxFilter->getName();
    }

    public function getLabel()
    {
        $sxFilter = $this->_sxFilter;
        $label = false;

        $activeValues = [];
        foreach ($sxFilter->getOptions() as $sxOption) {

            if ($sxOption->isActive()) {
                $activeValues[] = $sxOption->getName();
            }
        };

        $label = implode(', ', $activeValues);

        return $label ?? '...';
    }

    /**
     * Clear current element link text, for example 'Clear Price'
     *
     * @return false|string
     */
    public function getClearLinkText()
    {
        return __('clear');
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
     * Retrieve Clear Filters URL
     *
     * @return string
     */
    public function getRemoveUrl()
    {
        $filterState = [];

        foreach ($this->_sxHelper->getSetFilters() as $key => $value) {
            $key = 'sx_' . $key;
            if ($key == $this->getRequestVar()) continue;
            $filterState[$key] = $value;
        }

        $filterState[$this->getRequestVar()] = '';

        $params['_current'] = true;
        $params['_use_rewrite'] = true;
        $params['_query'] = $filterState;
        $params['_escape'] = true;
        return $this->_urlBuilder->getUrl('*/*/*', $params);
    }


    public function getFilter()
    {
        return $this;
    }
}
