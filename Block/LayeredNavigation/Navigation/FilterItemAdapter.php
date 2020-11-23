<?php

namespace Semknox\Productsearch\Block\LayeredNavigation\Navigation;

use Semknox\Productsearch\Helper\SxHelper;

class FilterItemAdapter extends \Magento\Catalog\Model\Layer\Filter\Item
{

    public function __construct(
        SxHelper $sxHelper,
        \Magento\Framework\UrlInterface $url,
        \Magento\Theme\Block\Html\Pager $htmlPagerBlock,
        array $data = []
    ) {
        $this->_sxHelper = $sxHelper;
        parent::__construct($url, $htmlPagerBlock, $data);
    }


    public function setSxOption($sxOption)
    {
        $this->_sxOption = $sxOption;
    }

    public function setSxFilter($sxFilter)
    {
        $this->_sxFilter = $sxFilter;
    }

    public function getCount()
    {
        return $this->_sxOption->getNumberOfResults();
    }

    public function getLabel()
    {
        return $this->_sxOption->getName(); 
    }

    public function getValue()
    {
        return $this->_sxOption->getValue(); 
    }

}