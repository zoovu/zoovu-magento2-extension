<?php

namespace Semknox\Productsearch\Block\CatalogSearch;

use Magento\Catalog\Block\Product\ListProduct as ParentListProduct;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Url\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Block\Product\Context;
use Magento\Search\Helper\Data as SearchHelper;

use Semknox\Productsearch\Helper\SxHelper;
use Semknox\Productsearch\Controller\SearchController;


class ListProduct extends ParentListProduct
{
        public function __construct(
        Context $context,
        PostHelper $postDataHelper,
        Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        Data $urlHelper,
        array $data = [],
        SxHelper $helper,
        CollectionFactory $productCollectionFactory,
        SearchHelper $searchHelper,
        SearchController $searchcontroller
    ) {
        $this->_sxHelper = $helper;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_searchHelper = $searchHelper;
        $this->sxSearch = $searchcontroller;

        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $data
        );
    }


    protected function _getProductCollection() {

        if ($this->_productCollection === null) {
            $this->_productCollection = $this->initializeProductCollection();
        }

        return $this->_productCollection;
    }

    private function initializeProductCollection() 
    {
        $toolbar = $this->getToolbarBlock();

        $productIds = $this->sxSearch->getArticles();

        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addFieldToFilter('entity_id', array('in' => $productIds));


        return $collection;

    }

}
