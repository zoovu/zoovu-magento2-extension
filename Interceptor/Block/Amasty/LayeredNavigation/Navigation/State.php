<?php

namespace Semknox\Productsearch\Interceptor\Block\Amasty\LayeredNavigation\Navigation;

use Semknox\Productsearch\Helper\SxHelper;

class State
{

    public function __construct(
        SxHelper $sxHelper
    ) {
        $this->_sxHelper = $sxHelper;
        $this->_isSxSearch = $sxHelper->isSearch() && $sxHelper->isSxSearchFrontendActive();
    }



    /**
     * Retrieve Clear Filters URL
     *
     * @return string
     */
    public function aroundViewLabel(\Amasty\Shopby\Block\Navigation\State $parent, callable $proceed, $filter)
    {
        if (!$this->_isSxSearch) {
            return $proceed($filter);
        }

        return $filter->getLabel();
    }

}
