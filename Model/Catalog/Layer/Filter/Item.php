<?php

namespace Semknox\Productsearch\Model\Catalog\Layer\Filter;

use Semknox\Productsearch\Helper\SxHelper;

class Item extends \Magento\Catalog\Model\Layer\Filter\Item
{

    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Theme\Block\Html\Pager $htmlPagerBlock,
        SxHelper $sxHelper,
        array $data = []
    ) {
        $this->_sxHelper = $sxHelper;
        $this->_isSxSearch = $sxHelper->isSearch() && $sxHelper->isSxSearchFrontendActive();

        $this->_url = $url;
        $this->_htmlPagerBlock = $htmlPagerBlock;
        parent::__construct($this->_url, $this->_htmlPagerBlock,$data);
    }

    public function getCount()
    {
        if (!$this->_isSxSearch || !isset($this->_sxOption)) return parent::getCount();

        return $this->_sxOption->getNumberOfResults();
    }

    public function getLabel()
    {
        if (!$this->_isSxSearch || !isset($this->_sxOption)) return parent::getLabel();

        return $this->_sxOption->getName(); 
    }

    public function getValue()
    {
        if (!$this->_isSxSearch || !isset($this->_sxOption)) return parent::getValue();

        return $this->_sxOption->getValue(); 
    }

}