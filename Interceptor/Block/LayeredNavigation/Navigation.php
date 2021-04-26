<?php

namespace Semknox\Productsearch\Interceptor\Block\LayeredNavigation;

use Semknox\Productsearch\Helper\SxHelper;

class Navigation 
{

    public function __construct(
        SxHelper $sxHelper,
        \Magento\LayeredNavigation\Block\Navigation\FilterRendererFactory $filterRendererFactory
    ) {
        $this->_sxHelper = $sxHelper;
        $this->_isSxSearch = $sxHelper->isSearch() && $sxHelper->isSxSearchFrontendActive();

        $this->_filterRenderer = $filterRendererFactory;
    }

    /**
     * Check availability display layer block
     *
     * @return bool
     */
    public function afterCanShowBlock(\Magento\LayeredNavigation\Block\Navigation $parent, $result)
    {
        if(!$this->_isSxSearch) return $result;

        $filterList = $this->_sxHelper->getSxResponseStore('filterList');
        return $filterList && count($filterList);
    }


    /**
     * Get all layer filters
     *
     * @return array
     */
    public function afterGetFilters(\Magento\LayeredNavigation\Block\Navigation $parent, $result)
    {
        if (!$this->_isSxSearch) return $result;

        $filterList = [];
        
        foreach($this->_sxHelper->getSxResponseStore('filterList',[]) as $sxFilter)
        {
            $filter = $this->_filterRenderer->create();
            $filter->setSxFilter($sxFilter);
            $filterList[] = $filter;
        }

        return $filterList;
    }

}
