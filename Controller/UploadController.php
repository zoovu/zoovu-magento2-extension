<?php

namespace Semknox\Productsearch\Controller;


use Semknox\Productsearch\Model\ArticleTransformer;
use Semknox\Productsearch\Helper\SxHelper;

use Semknox\Core\SxConfig;
use Semknox\Core\SxCore;
use Semknox\Core\Exceptions\DuplicateInstantiationException;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\UrlInterface;

class UploadController {

   
    private $_sxHelper, $_sxCore, $_sxConfig, $_sxUploader, $_sxUpdater;

    
    public function __construct(
        SxHelper $helper,
        StoreManagerInterface $storeManagerInterface,
        CollectionFactory $collectionFactory,
        ProductRepository $productRepository,
        CategoryCollectionFactory $categoryCollectionFactory
    ){
        $this->_sxHelper = $helper;
        $this->_storeManager = $storeManagerInterface;
        $this->_collectionFactory = $collectionFactory;
        $this->_productRepository = $productRepository;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
    }


    public function setConfig($configValues = [])
    {
        // really needed 
        $configValues['productTransformer'] = ArticleTransformer::class;
        $defaultValues['storagePath'] = $this->_sxHelper->get('sxFolder');

        $configValues = array_merge($defaultValues, $configValues);

        $this->_sxConfig = new SxConfig($configValues);
   
        try {
            $this->_sxCore = new SxCore($this->_sxConfig);
            $this->_sxUploader = $this->_sxCore->getInitialUploader();
            $this->_sxUpdater = $this->_sxCore->getProductUpdater();
        } catch (DuplicateInstantiationException $e) {
            $this->_sxHelper->log('Duplicate instantiation of uploader. Cronjob execution to close?');
            exit();
        }

        $this->config = $configValues;

    }

    /**
     * start new product upload
     * 
     */
    public function startFullUpload()
    {
        $shopId = $this->_sxConfig->get('shopId') ? $this->_sxConfig->get('shopId') : null;

        $productCollection = $this->_collectionFactory->create();
        $productCollection->addAttributeToSelect('*');
        $productCollection->addStoreFilter($shopId);  

        $mageArticleQty = $productCollection->getSize();

        if ($mageArticleQty) {
            $this->_sxUploader->startCollecting([
                'expectedNumberOfProducts' => $mageArticleQty
            ]);
        }
    }


    /**
     * continue running product upload
     * 
     */
    public function continueFullUpload()
    {

        if ($this->_sxUploader->isCollecting()) {

            // collecting
            $storeId = $this->_sxConfig->get('shopId');
            $collectBatchSize = $this->_sxConfig->get('collectBatchSize');
            $page = ((int) $this->_sxUploader->getNumberOfCollected() / $collectBatchSize) + 1;

            $productCollection = $this->_collectionFactory->create();
            $productCollection->addAttributeToSelect('*');
            $productCollection->addStoreFilter($storeId);
            $productCollection->setPageSize($collectBatchSize);
            $productCollection->setCurPage($page);

            $transformerArgs = [
                'categoryCollectionFactory' => $this->_categoryCollectionFactory,
                'sxConfig' => $this->_sxConfig
            ];

            foreach ($productCollection as $product) {

                $mageProduct = $this->_productRepository->getById($product->getId(), false, $storeId);
                $this->_sxUploader->addProduct($mageProduct, $transformerArgs);
            } 

        } else {

            // uploading


        }

    }


    /**
     * is currently an upload running for this config
     * 
     */
    public function isRunning()
    {
        return $this->_sxUploader->isRunning();
    }


    /**
     * collecting finished, ready to upload
     * 
     */
    public function isReadyToUpload()
    {
        if ($shopStatus = $this->getCurrentShopStatus()) {
            return $shopStatus->getPhase() == "UPLOADING" && $shopStatus->getCollectingProgress() >= 100;
        }

        return false;
    }


    /**
     * uploading finished, ready to finalize
     * 
     */
    public function isReadyToFinalize()
    {
        if ($shopStatus = $this->getCurrentShopStatus()) {
            return $shopStatus->getPhase() == "UPLOADING" && $shopStatus->getCollectingProgress() >= 100 && $shopStatus->getUploadingProgress() >= 100;
        }

        return false;
    }


    /**
     * get status of alle uploads
     * 
     */
    public function getCurrentShopStatus()
    {
        $shopId = $this->_sxConfig->get('shopId');
        $lang = $this->_sxConfig->get('lang');

        $status = $this->getStatus();

        if ($shopId && $lang && isset($status[$shopId . '-' . $lang])) {
            return $status[$shopId . '-' . $lang];
        }

        return false;
    }


    /**
     * get status of alle uploads
     * 34
     */
    public function getStatus()
    {
        $uploadOverview = $this->_sxCore->getInitialUploadOverview();

        $this->_sxHelper->log(json_encode($uploadOverview));

        return $uploadOverview->getRunningUploads();
    }


    /**
     * get the configs of all shops
     * 
     * @return array
     */
    public function getShopConfigs()
    {
        $sxShopConfigs = array();

        foreach($this->_storeManager->getStores() as $storeId => $store){

            $projectId = $this->_sxHelper->get('sxProjectId', $storeId, null);
            $apiKey = $this->_sxHelper->get('sxApiKey', $storeId, null);

            $storeIdentifier = $storeId . '-' . $store['code'];
            
            $currentShopConfig = [
                'projectId' => $projectId,
                'apiKey' => $apiKey,
                'sandbox' => $this->_sxHelper->get('sxIsSandbox', $storeId),

                'apiUrl' => $this->_sxHelper->get('sxIsSandbox', $storeId) ? $this->_sxHelper->get( 'sxSandboxApiUrl', $storeId) : $this->_sxHelper->get( 'sxApiUrl', $storeId),
                'shopId' => $storeId,
                'cronjobHour' => (int) $this->_sxHelper->get('sxCronjobHour', $storeId),
                'cronjobMinute' => (int) $this->_sxHelper->get('sxCronjobMinute', $storeId),

                'collectBatchSize' => (int) $this->_sxHelper->get('sxCollectBatchSize', $storeId),
                'uploadBatchSize' => (int) $this->_sxHelper->get('sxUploadBatchSize', $storeId),
                'requestTimeout' => (int) $this->_sxHelper->get('sxRequestTimeout', $storeId),

                'storeIdentifier' => $storeIdentifier,
                'storeRootCategory' => $store->getRootCategoryId(),

                // shopsystem settings
                'sxFrontendActive' => $this->_sxHelper->get('sxFrontendActive', $storeId, true),
                'sxUploadActive' => $this->_sxHelper->get('sxUploadActive', $storeId, true),
                'sxIncrementalUpdatesActive' => $this->_sxHelper->get('sxIncrementalUpdatesActive', $storeId, true),
                'sxAnswerActive' => $this->_sxHelper->get('sxAnswerActive', $storeId, true),
            ];

            if($projectId && $apiKey){
                $sxShopConfigs[$storeIdentifier] = $currentShopConfig;
            }

        }

        return $sxShopConfigs;
    
    }

    


}