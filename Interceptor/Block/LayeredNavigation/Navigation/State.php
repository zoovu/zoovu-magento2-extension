<?php

namespace Semknox\Productsearch\Interceptor\Block\LayeredNavigation\Navigation;

use Semknox\Productsearch\Helper\SxHelper;

class State
{

    public function __construct(
        SxHelper $sxHelper,
        \Semknox\Productsearch\Model\Filter\Factory $filterFactory,
        \Magento\Framework\View\Element\Context $context
    ) {
        $this->_sxHelper = $sxHelper;
        $this->_isSxSearch = $sxHelper->isSearch() && $sxHelper->isSxSearchFrontendActive();

        $this->_filterFactory = $filterFactory;
        $this->_urlBuilder = $context->getUrlBuilder();
    }


    /**
     * Get all layer filters
     *
     * @return array|Filter\AbstractFilter[]
     */
    public function afterGetActiveFilters(\Magento\LayeredNavigation\Block\Navigation\State $parent, $result)
    {
        if (!$this->_isSxSearch) return $result;

        $filterList = [];

        foreach ($this->_sxHelper->getSxResponseStore('activeFilters', []) as $sxFilter) {
            $filterList[] = $this->_filterFactory->create($sxFilter, ['data' => ['sxFilter' => $sxFilter]]);
        }

        return $filterList;
    }


    /**
     * Retrieve Clear Filters URL
     *
     * @return string
     */
    public function aroundGetClearUrl(\Magento\LayeredNavigation\Block\Navigation\State $parent, callable $proceed)
    {
        if (!$this->_isSxSearch) {
            return $proceed();
        }

        $filterState = [];

        foreach ($this->_sxHelper->getSxResponseStore('activeFilters', []) as $sxFilter) {
            $item = $this->_filterFactory->create($sxFilter, ['data' => ['sxFilter' => $sxFilter]]);
            $filterState[$item->getRequestVar()] = $item->getCleanValue();
        }

        $params['_current'] = true;
        $params['_use_rewrite'] = true;
        $params['_query'] = $filterState;
        $params['_escape'] = true;
        return $this->_urlBuilder->getUrl('*/*/*', $params);
    }



    /**
     * Get Amasty viewLabel
     *
     * @return string
     */
    public function afterGetViewLabel(\Magento\LayeredNavigation\Block\Navigation\State $parent, $result)
    {
        if (!$this->_isSxSearch) return $result;

        return 'test';
    }





}
