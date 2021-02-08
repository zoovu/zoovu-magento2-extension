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

        // availabe filters
        $this->_sxHelper->setSxResponseStore('filterList', $this->sxSearch->getAvailableFilters());

        // availabe range filters
        $rangeFilter = [];
        foreach ($this->sxSearch->getAvailableFilters() as $filter) {
            $rangeFilter = [];
            if ($filter->getType() == 'RANGE') {

                $activeMin = $filter->getActiveMin();
                if(is_array($activeMin) && isset($activeMin['value'])){
                    $activeOptions = explode('___', $filter->getActiveMin()['value'], 2);
                    $filter->setActiveOptions($activeOptions);
                }
            
                $rangeFilter[$filter->getName()] = $filter;
            }
        }
        $this->_sxHelper->setSxResponseStore('rangeFilter', $rangeFilter);

        // active filters
        $activeFilters = [];
        foreach($this->sxSearch->getActiveFilters() as $filter){

            if(isset($filter['max'])){
                //range
                
                $unit = isset($rangeFilter[$filter['name']]) ? $rangeFilter[$filter['name']]->getUnit() : '';
                $filter['values'][] = [
                    'value' => $filter['min'].'___'. $filter['max'],
                    'key' => $filter['min'] . '___' . $filter['max'],
                    'name' => $filter['min'] . " $unit - " . $filter['max'] . " $unit"            
                ];
            } 

            $activeFilters[] = $filter;
        }
        $this->_sxHelper->setSxResponseStore('activeFilters', $activeFilters);


        // get additional data...
        $collection->_sxAvailableOrders = $this->sxSearch->getAvailableOrders();
        $collection->_sxResultsCount = $this->sxSearch->getResultsCount();
        $collection->_sxLastPageNum = $this->sxSearch->getLastPageNum();
        $collection->_sxCurrentPage = $this->sxSearch->getCurrentPage();

        $collection->_sxContentResults = $this->sxSearch->getContentResults();

        $contentItemsPerPage = $this->_sxHelper->get('sxContentSearchResultsNumber',null, 0);
        $firstContentIdx = $collection->_sxCurrentPage == 1 ? 0 : ($collection->_sxCurrentPage - 1) * $contentItemsPerPage;

        foreach ($collection->_sxContentResults as $idx => $contentResult) {
            if ($idx < $firstContentIdx || $idx >= $firstContentIdx + $contentItemsPerPage) {
                unset($collection->_sxContentResults[$idx]);
            } 
        }
        $collection->_sxContentResults = array_values($collection->_sxContentResults);

        // add fake-product (content search)
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        foreach($collection->_sxContentResults as $idx => $contentResult)
        {
            $product = $objectManager->create('Magento\Catalog\Model\Product');
            $product->setId('sxcontent-' . $idx);
            $product->setName($contentResult->getName());
            $product->setRequestPath($contentResult->getLink());
            $product->setShortDescription('in '. $contentResult->getSectionName());
            $product->setData('salable', false);
            $collection->addItem($product);
        }


        return $collection;

    }


    /**
     * Retrieve additional blocks html
     *
     * @return string
     */
    public function getAdditionalHtml()
    {
        $collection = $this->_getProductCollection();

        $contentBoxesCount = count($collection->_sxContentResults);
        if(!$contentBoxesCount) return parent::getAdditionalHtml();

        $productBoxesCount = count($collection) - $contentBoxesCount; 
        $contentBoxEvery = ceil(($productBoxesCount / $contentBoxesCount) -1);

        $html = '<script>document.addEventListener("DOMContentLoaded", function() {';

        $html .= "var magePriceBoxes = document.getElementsByClassName('price-box');";

        $html .= "var sxContent = []";

        foreach ($collection->_sxContentResults as $idx => $contentResult) {
            
            // to increase compatibility to older mage2 versions
            $html .= "
                for(var i = 0; i < magePriceBoxes.length; i++){
                    if(magePriceBoxes[i].getAttribute('data-price-box') == 'product-id-sxcontent-". $idx. "'){
                        sxContent[" . $idx . "] = magePriceBoxes[i].parentNode.parentNode;
                        break;
                    }              
                };";

            $html .= "if(sxContent[" . $idx . "]){";

                // set Url
                $html .= "sxContent[" . $idx . "].getElementsByTagName('a')[0].href = '".$contentResult->getLink()."';";

                // remove product actions
                $html .= "sxContent[" . $idx . "].getElementsByClassName('product-item-actions')[0].remove();";
                
                // remove price container
                $html .= "document.getElementById('product-price-sxcontent-" . $idx . "').remove();";
                
                // set image
                $html .= "sxContent[" . $idx . "].getElementsByClassName('product-image-photo')[0].src = '".$contentResult->getImage()."';";

            $html .= "}";

        }

        // move boxes
        $html .= "var mageProductList = document.getElementsByClassName('product-item');";
        $html .= "
                var contentBoxCounter = 0;
                var contentResultIdx = 0;
                for (var i = $contentBoxesCount; i < mageProductList.length; i++) {

                    if(contentBoxCounter == ".$contentBoxEvery. " && sxContent[contentResultIdx]){
                        contentBox = sxContent[contentResultIdx];
                        mageProductList[i].parentNode.insertBefore(contentBox.parentNode, mageProductList[i]);
                        contentBoxCounter = 0;
                        i--;
                        contentResultIdx++;
                    } else {
                        contentBoxCounter++;
                    }

                }";
        
        $html .= '});</script>';


        return $html.parent::getChildHtml('additional');
    }

}
