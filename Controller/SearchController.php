<?php

namespace Semknox\Productsearch\Controller;

use Semknox\Productsearch\Helper\SxHelper;
use Semknox\Core\SxCore;
use Semknox\Core\SxConfig;
use Magento\Search\Helper\Data as SearchHelper;
use Magento\Catalog\Block\Product\ProductList\Toolbar;

class SearchController 
{
    private $_sxSearchResponse;

    public function __construct(
        SxHelper $helper,
        SearchHelper $searchHelper,
        Toolbar $toolbar
    ) {
        $this->_sxHelper = $helper;
        $this->_searchHelper = $searchHelper;
        $this->_toolbar = $toolbar;
    }

    private function _getSearchResponse()
    {
        if($this->_sxSearchResponse){
            return $this->_sxSearchResponse;
        }
        
        $searchQuery = $this->_searchHelper->getEscapedQueryText();

        $shopConfig = $this->_sxHelper->getConfig();
        if (!$shopConfig) return [];

        $this->_sxConfig = new SxConfig($shopConfig);
        $this->_sxCore = new SxCore($this->_sxConfig);
        $this->_sxSearch = $this->_sxCore->getSearch();

        $limit = $this->_toolbar->getLimit();
        $page = $this->_toolbar->getCurrentPage();

        $sxSearch = $this->_sxSearch->query($searchQuery);
        $sxSearch->setLimit($limit);
        $sxSearch->setPage($page);


        try {
            // do search...
            $this->_sxSearchResponse = $sxSearch->search();
            return $this->_sxSearchResponse; 
        } catch (\Exception $e) {
            $this->_sxHelper->log($e->getMessage());
        }

    }

    public function getArticles()
    {
        $articleIds = [];
        foreach($this->_getSearchResponse()->getProducts() as $sxArticle) {
            $articleIds[] = $sxArticle->getId();
        }

        return $articleIds;
    }


    public function getAvailableOrders()
    {
        $availableOrders = [];

        foreach($this->_getSearchResponse()->getAvailableSortingOptions() as $option){
            $availableOrders[$option->getKey()] = $option->getName();
        };

        if(!$availableOrders) $availableOrders = ['sxName' => 'sxName', 'sxPrice' => 'sxPrice'];

        return $availableOrders;
    }

    public function getSearchInterpretation()
    {
        return (string) $this->_getSearchResponse()->getAnswerText();
    }

    public function getResultsCount()
    {
        return $this->_getSearchResponse()->getTotalProductResults();
    }

}
