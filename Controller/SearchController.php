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

    public function isSxSearchAvailable()
    {
        return !!$this->_getSearchResponse();
    }

    private function _getSearchResponse()
    {
        if(!$this->_sxHelper->isSxSearchFrontendActive()){
            return false;
        }

        if($this->_sxSearchResponse){
            return $this->_sxSearchResponse;
        }
        
        $searchQuery = $this->_searchHelper->getEscapedQueryText();

        $shopConfig = $this->_sxHelper->getConfig();
        if (!$shopConfig) return [];

        //$shopConfig['requestTimeout'] = '5'; // for search request should not be longer
        $shopConfig['requestTimeout'] = $shopConfig['requestTimeoutFrontend'];

        $this->_sxConfig = new SxConfig($shopConfig);
        $this->_sxCore = new SxCore($this->_sxConfig);
        $this->_sxSearch = $this->_sxCore->getSearch();

        $limit = $this->_toolbar->getLimit();
        $page = $this->_toolbar->getCurrentPage();

        $sxSearch = $this->_sxSearch->query($searchQuery);
        $sxSearch->setLimit($limit);
        $sxSearch->setPage($page);

        
        foreach($this->_sxHelper->getSetFilters() as $filter => $value)
        {
            $value = explode('___', $value, 2);
            $sxSearch->addFilter($filter,$value);
        }

        if($order = $this->_sxHelper->getSetOrder()){
            $sxSearch->sortBy($order, $this->_sxHelper->getSetDir());
        }

        try {
            // do search...
            $this->_sxSearchResponse = $sxSearch->search();
            //$this->_sxHelper->isSxSearch = true;

            $this->_sxHelper->isSxSearchActive = true;
        } catch (\Exception $e) {
            $this->_sxSearchResponse = false;

            $this->_sxHelper->log($e->getMessage());
            $this->_sxHelper->isSxSearchActive = false;
        }

        return $this->_sxSearchResponse;

    }

    public function getArticles()
    {
        $articleIds = [];

        if(!$this->_getSearchResponse()) return $articleIds;

        foreach($this->_getSearchResponse()->getProducts() as $sxArticle) {
            $articleIds[] = $sxArticle->getId();
        }

        return $articleIds;
    }


    public function getAvailableOrders()
    {
        $availableOrders = [];

        if (!$this->_getSearchResponse() || !$this->getResultsCount()) return $availableOrders;

        foreach($this->_getSearchResponse()->getAvailableSortingOptions() as $option){
            $availableOrders[$option->getKey()] = $option->getName();
        };

        //if(!$availableOrders) $availableOrders = ['sxName' => 'sxName', 'sxPrice' => 'sxPrice']; // for testing

        return $availableOrders;
    }

    public function getSearchInterpretation()
    {
        if (!$this->_getSearchResponse()) return false;

        return (string) $this->_getSearchResponse()->getAnswerText();
    }

    public function getAvailableFilters()
    {
        if (!$this->_getSearchResponse() || !$this->getResultsCount()) return [];

        return $this->_getSearchResponse()->getAvailableFilters() ;
    }

    public function getActiveFilters()
    {
        $activeFilters = array();
        foreach($this->getAvailableFilters() as $filter){

            if(!$filter->isActive()) continue;
            $activeFilters[] = $filter;
        }
        
        return $activeFilters;
    }

    public function getLastPageNum()
    {
        if (!$this->_getSearchResponse()) return false;

        return ceil($this->getResultsCount() / $this->_toolbar->getLimit());
    }

    public function getCurrentPage()
    {
        if (!$this->_getSearchResponse()) return false;

        return $this->_toolbar->getCurrentPage();
    }

    public function getResultsCount()
    {
        if (!$this->_getSearchResponse()) return false;
        
        return $this->_getSearchResponse()->getTotalProductResults();
    }


    public function getContentResults()
    {
        if (!$this->_getSearchResponse()) return [];
        
        $contentResults = $this->_getSearchResponse()->getResults('custom');
        if(!$contentResults) return $contentResults;

        // sort by relevance
        usort($contentResults, function ($first, $second) {
            return $first->getRelevance() < $second->getRelevance();
        });

        return $contentResults;
    }


}
