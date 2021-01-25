<?php

namespace Semknox\Productsearch\Block\CatalogSearch\SearchResult;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Url\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Block\Product\Context;
use Magento\Search\Helper\Data as SearchHelper;

use Semknox\Productsearch\Helper\SxHelper;
use Semknox\Productsearch\Controller\SearchController;


class ListProduct extends \Magento\Catalog\Block\Product\ListProduct
{
    public function __construct(
        SxHelper $helper,
        CollectionFactory $productCollectionFactory,
        SearchHelper $searchHelper,
        SearchController $searchcontroller,
        Context $context,
        PostHelper $postDataHelper,
        Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        Data $urlHelper,
        array $data = []
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


    protected function _getProductCollection() 
    {

        if ($this->_productCollection === null) {
            $this->_productCollection = $this->initializeProductCollection();
        }

        return $this->_productCollection;
    }

    private function initializeProductCollection() 
    {
        if(!$this->_sxHelper->isSxSearchFrontendActive()){
            $collection = parent::_getProductCollection();
            $collection->_isSxSearch = false;
            return $collection;
        } 

        $productIds = $this->sxSearch->getArticles();

        // get collection
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');

        if(count($productIds)){
            $collection->addAttributeToSelect('*');
            $collection->addFieldToFilter('entity_id', array('in' => $productIds));
            $collection->getSelect()->order(new \Zend_Db_Expr('FIELD(e.entity_id, ' . implode(',', $productIds) . ')'));
        } else {
            $collection->addFieldToFilter('entity_id',0);
        }


        // mark as semknox search
        $collection->_isSxSearch = true;

        $this->_sxHelper->setSxResponseStore('filterList', $this->sxSearch->getAvailableFilters());
        $this->_sxHelper->setSxResponseStore('activeFilters', $this->sxSearch->getActiveFilters());

        // get additional data...
        $collection->_sxAvailableOrders = $this->sxSearch->getAvailableOrders();
        $collection->_sxResultsCount = $this->sxSearch->getResultsCount();
        $collection->_sxLastPageNum = $this->sxSearch->getLastPageNum();
        $collection->_sxCurrentPage = $this->sxSearch->getCurrentPage();



        // add fake-product (content search)
        var_dump($this->sxSearch->getContentResults());

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('Magento\Catalog\Model\Product');
        $product->setName('Toller Blogartikel');
        $product->setRequestPath('path/to/blog/request-path.html');
        $product->setShortDescription('Hallo wie geht es dir?');
        $product->setData('salable', false);
        $product->setId('sxcontent');
        $collection->addItem($product);


        $product = $objectManager->create('Magento\Catalog\Model\Product');
        $product->setName('Toller Blogartikel 2');
        //$product->setProductUrl('/test');
        $collection->addItem($product);

        var_dump(count($collection));
        


        return $collection;

    }

}
