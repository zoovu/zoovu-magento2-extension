<?php

namespace Semknox\Productsearch\Interceptor\Block\LayeredNavigation;

use Semknox\Productsearch\Helper\SxHelper;

class Navigation 
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
     * @return array|Filter\AbstractFilter[]
     */
    public function afterGetFilters(\Magento\LayeredNavigation\Block\Navigation $parent, $result)
    {
        if (!$this->_isSxSearch) return $result;

        $filterList = [];
        
        foreach($this->_sxHelper->getSxResponseStore('filterList',[]) as $sxFilter)
        {
            $filter = $this->_filterFactory->create($sxFilter, ['data' => ['sxFilter' => $sxFilter]]);

            if(!$filter) continue;
            
            $filterList[] = $this->_filterFactory->create($sxFilter, ['data' => ['sxFilter' => $sxFilter]]);
        }

        return $filterList;
    }


}
