<?php

namespace Semknox\Productsearch\Interceptor\Block\CatalogSearch;

use Semknox\Productsearch\Helper\SxHelper;

class Result
{

    /**
     * @param Context $context
     * @param LayerResolver $layerResolver
     * @param Data $catalogSearchData
     * @param QueryFactory $queryFactory
     * @param array $data
     */
    public function __construct(
        SxHelper $sxHelper
    ) {
        $this->_sxHelper = $sxHelper;
        $this->_isSxSearch = $sxHelper->isSearch() && $sxHelper->isSxSearchFrontendActive();
    }

    /**
     * Get search query text
     *
     * @return \Magento\Framework\Phrase
     */
    public function afterGetSearchQueryText(\Magento\CatalogSearch\Block\Result $parent, $result)
    {
        if (!$this->_isSxSearch || !$this->_sxHelper->isSxAnswerActive()) return $result;

        $productList = $parent->getListBlock();
        if (!isset($productList->sxSearch)) return $result;

        return strip_tags($productList->sxSearch->getSearchInterpretation());
        
    }


}
