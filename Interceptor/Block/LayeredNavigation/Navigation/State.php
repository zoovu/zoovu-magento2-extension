<?php

namespace Semknox\Productsearch\Interceptor\Block\LayeredNavigation\Navigation;

use Semknox\Productsearch\Helper\SxHelper;

class State
{

    public function __construct(
        SxHelper $sxHelper,
        \Magento\Framework\View\Element\Context $context
    ) {
        $this->_sxHelper = $sxHelper;
        $this->_isSxSearch = $sxHelper->isSearch() && $sxHelper->isSxSearchFrontendActive();

        $this->_urlBuilder = $context->getUrlBuilder();
    }


    /**
     * Retrieve Clear Filters URL
     *
     * @return string
     */
    public function aroundGetClearUrl(\Magento\LayeredNavigation\Block\Navigation\State $parent, callable $proceed)
    {
        if (!$this->_isSxSearch){
            return $proceed();
        }

        $filterState = [];

        foreach ($parent->getActiveFilters() as $item) {
            $filterState[$item->getRequestVar()] = $item->getCleanValue();
        }

        $params['_current'] = true;
        $params['_use_rewrite'] = true;
        $params['_query'] = $filterState;
        $params['_escape'] = true;
        return $this->_urlBuilder->getUrl('*/*/*', $params);
    }


}
