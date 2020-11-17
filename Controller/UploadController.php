<?php

namespace Semknox\Productsearch\Controller;


use Semknox\Productsearch\Model\ArticleTransformer;
use Semknox\Productsearch\Helper\SxHelper;

use Semknox\Core\SxConfig;
use Semknox\Core\SxCore;
use Semknox\Core\Exceptions\DuplicateInstantiationException;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class UploadController {

   
    private $_sxHelper, $_sxCore, $_sxConfig, $_sxUploader, $_sxUpdater;

    
    public function __construct(
        SxHelper $helper,
        StoreManagerInterface $storeManagerInterface,
        CollectionFactory $collectionFactory,
        ProductRepository $productRepository,
        CategoryCollectionFactory $categoryCollectionFactory,
        Product $productModel,
        Status $productStatus
    ){
        $this->_sxHelper = $helper;
        $this->_storeManager = $storeManagerInterface;
        $this->_collectionFactory = $collectionFactory;
        $this->_productRepository = $productRepository;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_productModel = $productModel;
        $this->_productStatus = $productStatus;
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
            $this->_sxHelper->log('Duplicate instantiation of uploader. Cronjob execution to close?', 'error');
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
            $productCollection->addAttributeToFilter('status', ['in' => $this->_productStatus->getVisibleStatusIds()]);
            $productCollection->addStoreFilter($storeId);
            $productCollection->setPageSize($collectBatchSize);
            $productCollection->setCurPage($page);

            $store = $this->_storeManager->getStore($storeId);

            $transformerArgs = [
                'categoryCollectionFactory' => $this->_categoryCollectionFactory,
                'sxConfig' => $this->_sxConfig,
                'mediaUrl' => $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA),
                'currency' => $store->getCurrentCurrency()->getCode(),
                'productResourceModel' => $this->_productModel
            ];

            $productCounter = 0;
            foreach ($productCollection as $product) {
                $mageProduct = $this->_productRepository->getById($product->getId(), false, $storeId);
                $this->_sxUploader->addProduct($mageProduct, $transformerArgs);
                $productCounter++;
            }

            // if ready, start uploading
            if ($productCounter < $collectBatchSize) {

                $response = $this->_sxUploader->startUploading();

                if (isset($response['status']) && $response['status'] !== 'success') {
                    $this->_sxHelper->log($response['status'] . ' - ' . $response['message'], 'error');
                }
            }

        } else {

            // uploading

            // continue uploading...
            $response = $this->_sxUploader->sendUploadBatch(true);

            
            if (isset($response['validation'][0]['schemaErrors'][0])) {
                $this->_sxHelper->log(json_encode($response), 'error');
            }

            if (isset($response['status']) && $response['status'] !== 'success') {
                $this->_sxHelper->log(json_encode($response), 'error');
            }

        }

    }

    /**
     * finalize running product upload
     * 
     */
    public function finalizeFullUpload($signalApi = true)
    {
        $response = $this->_sxUploader->finalizeUpload($signalApi);

        if (isset($response['status']) && $response['status'] !== 'success') {
            $this->_sxHelper->log(json_encode($response), 'error');
        }
    }

    /**
     * stop running product upload
     * 
     */
    public function stopFullUpload()
    {
        if ($this->isRunning()) {
            $this->_sxUploader->abort();
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
        $storeIdentifier = $this->_sxConfig->get('storeIdentifier');

        $status = $this->getStatus();

        if ($storeIdentifier && isset($status[$storeIdentifier])) {
            return $status[$storeIdentifier];
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
        return $uploadOverview->getRunningUploads();
    }


}