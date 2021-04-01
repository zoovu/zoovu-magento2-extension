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
use Magento\Catalog\Helper\ImageFactory as ImageHelper;
use Magento\Framework\View\Asset\Repository as AssetRepos;
use Magento\Store\Model\App\Emulation as AppEmulation;
use Magento\Catalog\Model\Product\Visibility;

class UploadController {

   
    private $_sxHelper, $_sxCore, $_sxConfig, $_sxUploader, $_sxUpdater;

    private $_sxTransformerArgs = [];

    
    public function __construct(
        AppEmulation $appEmulation,
        ImageHelper $imageHelper,
        AssetRepos $assetRepos,
        SxHelper $helper,
        StoreManagerInterface $storeManagerInterface,
        CollectionFactory $collectionFactory,
        ProductRepository $productRepository,
        CategoryCollectionFactory $categoryCollectionFactory,
        Product $productModel,
        Visibility $productVisibility,
        Status $productStatus
    ){
        $this->appEmulation = $appEmulation;
        $this->mageImageHelper = $imageHelper;
        $this->mageAssetsRepos = $assetRepos;
        $this->_sxHelper = $helper;
        $this->_storeManager = $storeManagerInterface;
        $this->_collectionFactory = $collectionFactory;
        $this->_productRepository = $productRepository;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_productModel = $productModel;
        $this->_productStatus = $productStatus;
        $this->_productVisibility = $productVisibility;
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

            $storeId = $this->_sxConfig->get('shopId');
            $store = $this->_storeManager->getStore($storeId);
            $this->_sxTransformerArgs = [
                'categoryCollectionFactory' => $this->_categoryCollectionFactory,
                'sxConfig' => $this->_sxConfig,
                'mediaUrl' => $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA),
                'currency' => $store->getCurrentCurrency()->getCode(),
                'productResourceModel' => $this->_productModel,
                'imageHelper' => $this->mageImageHelper,
                'assetsRepos' => $this->mageAssetsRepos,
                'appEmulation' => $this->appEmulation,
                'sxHelper' =>  $this->_sxHelper
            ];

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
        $storeId = $this->_sxConfig->get('shopId') ? $this->_sxConfig->get('shopId') : null;

        $productCollection = $this->getUploadProductCollection($storeId); 
        $mageArticleQty = $productCollection->getSize();

        if ($mageArticleQty) {
            $this->_sxUploader->startCollecting([
                'expectedNumberOfProducts' => $mageArticleQty
            ]);
        }
    }

    public function getUploadProductCollection($storeId = false)
    {
        $productCollection = $this->_collectionFactory->create();

        if($storeId) {
            $productCollection->addStoreFilter($storeId);
            $this->appEmulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);
        }

        $productCollection->addAttributeToSelect('*');
        $productCollection->addAttributeToFilter('status', ['in' => $this->_productStatus->getVisibleStatusIds()]);
        $productCollection->setVisibility($this->_productVisibility->getVisibleInSearchIds());

        if($this->_sxHelper->sxUploadProductsWithStatusOutOfStock()){
            $productCollection->setFlag('has_stock_status_filter', false);
        }
        
        if(!$this->_sxHelper->sxUploadProductsWithZeroQuantity()){
            $productCollection->joinField('qty', 'cataloginventory_stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left');
            $productCollection->addAttributeToFilter('qty',['gt'=>0]);
        }

        $this->appEmulation->stopEnvironmentEmulation();

        return $productCollection;
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

            $productCollection = $this->getUploadProductCollection($storeId);
            $productCollection->setPageSize($collectBatchSize);
            $productCollection->setCurPage($page);

            $productCounter = 0;
            foreach ($productCollection as $product) {
                $mageProduct = $this->_productRepository->getById($product->getId(), false, $storeId);
                $this->_sxUploader->addProduct($mageProduct, $this->_sxTransformerArgs);
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


    /**
     * add single product update
     */
    public function addProductUpdates($productCollection = [])
    {
        $storeId = $this->_sxConfig->get('shopId');
        $productCounterTotal = 0;

        foreach ($productCollection as $product) {
            $mageProduct = $this->_productRepository->getById($product->getId(), false, $storeId);
            $this->_sxUpdater->addProduct($mageProduct, $this->_sxTransformerArgs);
            $productCounterTotal++;
        }

        if($productCounterTotal){
            $this->_sxHelper->log("$productCounterTotal products added to update-queue", 'success');
        }
        
    }

    /**
     * send single update product queue
     */
    public function sendProductUpdates()
    {
        if($this->_sxUpdater->sendUploadBatch()){
            $this->_sxHelper->log("product updates from queue sent", 'success');
        }

    }

}