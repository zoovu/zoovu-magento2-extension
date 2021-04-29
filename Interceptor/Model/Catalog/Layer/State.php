<?php

namespace Semknox\Productsearch\Interceptor\Model\Catalog\Layer;

use Semknox\Productsearch\Helper\SxHelper;

class State 
{

    public function __construct(
        SxHelper $sxHelper,
        \Magento\Framework\View\Element\Context $context,
        \Magento\LayeredNavigation\Block\Navigation\FilterRendererFactory $filterRendererFactory
    ) {
        $this->_sxHelper = $sxHelper;
        $this->_isSxSearch = $sxHelper->isSearch() && $sxHelper->isSxSearchFrontendActive();

        $this->_urlBuilder = $context->getUrlBuilder();
        $this->_filterRenderer = $filterRendererFactory; 
    }


    public function afterGetFilters(\Magento\Catalog\Model\Layer\State $parent, $result)
    {
        if (!$this->_isSxSearch) return $result;

        $filterList = [];

        foreach ($this->_sxHelper->getSxResponseStore('activeFilters', []) as $sxFilter) {

            $filter = $this->_filterRenderer->create();
            $filter->isActiveFilter = true;
            
            foreach($sxFilter['values'] as $value){
                $sxFilter['value'] = $value;
                $filter->_sxFilter = $sxFilter;
                $filterList[] = $filter;
            }
        }

        // to make filterlist everywhere available
        foreach($filterList as &$filter){
            $filter->activeFilterList = $filterList;
        }

        return $filterList;
    }
    
}
