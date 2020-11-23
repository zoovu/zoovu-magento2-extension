<?php

namespace Semknox\Productsearch\Block\LayeredNavigation\Navigation;

use Semknox\Productsearch\Helper\SxHelper;

class Search extends \Magento\LayeredNavigation\Block\Navigation
{

    /**
     * @param Template\Context $context
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Catalog\Model\Layer\FilterList $filterList
     * @param \Magento\Catalog\Model\Layer\AvailabilityFlagInterface $visibilityFlag
     * @param array $data
     */
    public function __construct(
        SxHelper $sxHelper,
        \Magento\LayeredNavigation\Block\Navigation\FilterRendererFactory $filterRendererFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Model\Layer\FilterList $filterList,
        \Magento\Catalog\Model\Layer\AvailabilityFlagInterface $visibilityFlag,
        array $data = []
    ) {
        $this->_sxHelper = $sxHelper;
        $this->_filterRenderer = $filterRendererFactory;

        parent::__construct(
            $context,
            $layerResolver,
            $filterList,
            $visibilityFlag,
            $data
        );
    }

    /**
     * Check availability display layer block
     *
     * @return bool
     */
    public function canShowBlock()
    {
        if(!$this->_sxHelper->isSxSearchFrontendActive()) return parent::canShowBlock();

        $filterList = $this->_sxHelper->getSxResponseStore('filterList');
        return $filterList && count($filterList);
    }


    /**
     * Get all layer filters
     *
     * @return array
     */
    public function getFilters()
    {
        if (!$this->_sxHelper->isSxSearchFrontendActive()) return parent::getFilters();

        $filterList = [];
        
        foreach($this->_sxHelper->getSxResponseStore('filterList') as $sxFilter)
        {
            $filter = $this->_filterRenderer->create();
            $filter->setSxFilter($sxFilter);
            $filterList[] = $filter;
        }

        return $filterList;
    }

}
