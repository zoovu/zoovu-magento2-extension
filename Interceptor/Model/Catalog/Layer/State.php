<?php

namespace Semknox\Productsearch\Interceptor\Model\Catalog\Layer;

use Semknox\Productsearch\Helper\SxHelper;

class State 
{

    public function __construct(
        SxHelper $sxHelper,
        \Magento\Framework\View\Element\Context $context,
        \Semknox\Productsearch\Model\Filter\Factory $filterFactory
    ) {
        $this->_sxHelper = $sxHelper;
        $this->_isSxSearch = $sxHelper->isSearch() && $sxHelper->isSxSearchFrontendActive();

        $this->_urlBuilder = $context->getUrlBuilder();
        $this->_filterFactory = $filterFactory;
    }

    public function afterGetFilters(\Magento\Catalog\Model\Layer\State $parent, $result)
    {
        if (!$this->_isSxSearch) return $result;

        $filterList = [];

        foreach ($this->_sxHelper->getSxResponseStore('activeFilters', []) as $sxFilter) {
            $filterList[] = $this->_filterFactory->create($sxFilter, ['data' => ['sxFilter' => $sxFilter]]);
        }

        return $filterList;
    }
    
}
