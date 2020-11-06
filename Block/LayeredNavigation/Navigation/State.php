<?php

namespace Semknox\Productsearch\Block\LayeredNavigation\Navigation;


class State extends \Magento\LayeredNavigation\Block\Navigation\State
{


    /**
     * Retrieve Clear Filters URL
     *
     * @return string
     */
    public function getClearUrl()
    {
        $filterState = [];

        foreach ($this->getActiveFilters() as $item) {
            $filterState[$item->getRequestVar()] = $item->getCleanValue();
        }

        $params['_current'] = true;
        $params['_use_rewrite'] = true;
        $params['_query'] = $filterState;
        $params['_escape'] = true;
        return $this->_urlBuilder->getUrl('*/*/*', $params);
    }


}
