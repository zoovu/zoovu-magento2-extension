<?php

namespace Semknox\Productsearch\Interceptor\Block\LayeredNavigation\Navigation;

use Semknox\Productsearch\Helper\SxHelper;


class FilterRenderer
{

    public function __construct(
        SxHelper $sxHelper
    ) {
        $this->_sxHelper = $sxHelper;
        $this->_isSxSearch = $sxHelper->isSearch() && $sxHelper->isSxSearchFrontendActive();
    }

    public function afterRender(\Magento\LayeredNavigation\Block\Navigation\FilterRenderer $parent, $result, $filter)
    {
        if (!$this->_isSxSearch || (isset($filter->_sxFilter) && $filter->_sxFilter->getType() !== 'RANGE')) {
            return $result;
        }

        $sxFilter = $filter->_sxFilter;

        // todo: move to template file
        return "<div class='slider-wrapper'>
                        <div class='slider sxRangeFilter' id='sx_" . $filter->getName() . "' 
                            data-start='" . $sxFilter->getActiveMin() . "'
                            data-end='" . $sxFilter->getActiveMax() . "'
                            data-range-min='" . $sxFilter->getMin() . "'
                            data-range-max='" . $sxFilter->getMax() . "'
                            data-url='" . $filter->getRemoveUrl() . "'
                        ></div>
                        <div class='slider-helper'>
                            <input class='start' value='' min='' type='number' name='num1'>
                            <span>-</span>
                            <input class='end' value='' max='' type='number' name='num2'>
                            <span class='unit'>" . $sxFilter->getUnit() . "</span>
                            <button class='' type='button'><i class='fa fa-angle-right'></i></button>
                        </div>
                    </div>";
    }
}