<?php

namespace Semknox\Productsearch\Interceptor\Block\LayeredNavigation\Navigation;

use Semknox\Productsearch\Helper\SxHelper;

class FilterRenderer 
{

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        SxHelper $sxHelper
    )
    {
        $this->_sxHelper = $sxHelper;
        $this->_isSxSearch = $sxHelper->isSearch() && $sxHelper->isSxSearchFrontendActive();
    }


    /**
     * @param FilterInterface $filter
     * @return string
     */
    public function afterRender(\Magento\LayeredNavigation\Block\Navigation\FilterRenderer $parent, $result, $filter)
    {
        if (!$this->_isSxSearch) return $result;

        // if range.. do nouislider
        $rangeFilter = $this->_sxHelper->getSxResponseStore('rangeFilter', []);

        if(count($rangeFilter) && is_string($filter->getName()) && isset($rangeFilter[$filter->getName()])){
            $sxFilter = $rangeFilter[$filter->getName()];

            return "<div class='slider-wrapper'>
                        <div class='slider sxRangeFilter' id='sx_". $filter->getName(). "' 
                            data-start='". $sxFilter->getActiveMin(). "'
                            data-end='" . $sxFilter->getActiveMax() . "'
                            data-range-min='" . $sxFilter->getMin() . "'
                            data-range-max='" . $sxFilter->getMax() . "'
                            data-url='". $filter->getRemoveUrl(). "'
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
        
        return $result;

    }

    /*

    public function afterGetRequestVar(\Magento\LayeredNavigation\Block\Navigation\FilterRenderer $parent, $result)
    {
        if (!$this->_isSxSearch) return $result;

        return 'sx_' . \urlencode($parent->getName());
    }

    public function afterGetResetValue(\Magento\LayeredNavigation\Block\Navigation\FilterRenderer $parent, $result)
    {
        if (!$this->_isSxSearch) return $result;

        return '';
    }

    public function afterGetCleanValue(\Magento\LayeredNavigation\Block\Navigation\FilterRenderer $parent, $result)
    {
        if (!$this->_isSxSearch) return $result;

        return '';
    }
    */

}
