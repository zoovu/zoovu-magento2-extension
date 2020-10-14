<?php

namespace Semknox\Productsearch\Block\CatalogSearch;

use Magento\CatalogSearch\Block\Result as CatalogSearchResult;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Framework\View\Element\Template\Context;
use Magento\CatalogSearch\Helper\Data;
use Magento\Search\Model\QueryFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

use Semknox\Productsearch\Helper\SxHelper;


class Result extends CatalogSearchResult
{

    /**
     * @param Context $context
     * @param LayerResolver $layerResolver
     * @param Data $catalogSearchData
     * @param QueryFactory $queryFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        LayerResolver $layerResolver,
        Data $catalogSearchData,
        QueryFactory $queryFactory,
        array $data = [],
        SxHelper $helper,
        CollectionFactory $productCollectionFactory
    ) {
        $this->_sxHelper = $helper;
        $this->_productCollectionFactory = $productCollectionFactory;

        parent::__construct($context, $layerResolver, $catalogSearchData,$queryFactory, $data);
    }

   
    /**
     * Get search query text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getSearchQueryText()
    {
        $prefix = $this->_sxHelper->get('sxRequestTimeout', 'test');

        return $prefix.': ' .parent::getSearchQueryText();
    }


    /**
     * Retrieve loaded category collection
     *
     * @return Collection
     */
    protected function _getProductCollection()
    {
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->setPageSize(3); 
        
        return $collection;

        return parent::_getProductCollection();
    }


}
