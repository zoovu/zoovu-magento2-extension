<?php

namespace Semknox\Productsearch\Controller;


use Semknox\Productsearch\Model\ArticleTransformer;
use Semknox\Productsearch\Helper\SxHelper;
use Semknox\Productsearch\Helper\SxLogger;


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
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Store\Model\App\Emulation as AppEmulation;
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class UploadController {

   
    private $_sxHelper, $_sxCore, $_sxConfig, $_sxUploader, $_sxUpdater;

    private $_sxTransformerArgs = [];

    
    public function __construct(
        AppEmulation $appEmulation,
        ImageHelper $imageHelper,
        AssetRepos $assetRepos,
        StockItemRepository $stockItemRepository,
        Configurable $configurable,
        SxHelper $helper,
        SxLogger $logger,
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
        $this->mageStockItem = $stockItemRepository;
        $this->mageConfigurableProduct = $configurable;
        $this->_sxHelper = $helper;
        $this->_sxLogger = $logger;
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
        $configValues['loggingService'] = $this->_sxLogger;
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
            $this->_sxLogger->log('Duplicate instantiation of uploader. Cronjob execution to close?', 'error');
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

            $this->_sxLogger->log("+/- $mageArticleQty products in this upload expected");
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

            $startUploading = false;

            // collecting
            $storeId = $this->_sxConfig->get('shopId');
            $collectBatchSize = $this->_sxConfig->get('collectBatchSize');
            $page = (int) ($this->_sxUploader->getNumberOfCollected() / $collectBatchSize) + 1;

            $productCollection = $this->getUploadProductCollection($storeId);
            $productCollection->setPageSize($collectBatchSize);
            $productCollection->setCurPage($page);

            $ignoreQuantity = $this->_sxHelper->sxUploadProductsWithZeroQuantity();
            $ignoreOutOfStockStatus = $this->_sxHelper->sxUploadProductsWithStatusOutOfStock();

            $cachedParents = [];

            $productCounter = 0;
            $productsSortedOut = 0;
            foreach ($productCollection as $product) {

                $productCounter++;
                
                $mageProduct = $this->_productRepository->getById($product->getId(), false, $storeId);
                $mageProductType = $mageProduct->getTypeInstance();

                // get parent if is child of configurable
                $parentId = $this->mageConfigurableProduct->getParentIdsByChild($product->getId());
                $mageProduct->sxGroupIdenifier = isset($parentId[0]) ? $parentId[0] : false;

                $parent = false;
                if($mageProduct->sxGroupIdenifier){

                    if(!array_key_exists($mageProduct->sxGroupIdenifier,$cachedParents)){
                        $cachedParents[$mageProduct->sxGroupIdenifier] = $this->_productRepository->getById($mageProduct->sxGroupIdenifier, false, $storeId) ?? false;
                    }
                    $parent = $cachedParents[$mageProduct->sxGroupIdenifier];
                }

                // todo: for group and bundle products
                // ...


                // check visibility
                $productVisible = stripos($mageProduct->getAttributeText('visibility'),'Search') !== false;
                if($parent){

                    $parentVisible = stripos($parent->getAttributeText('visibility'),'Search') !== false;
                    
                    if($productVisible){
                        $mageProduct->sxGroupIdenifier = $mageProduct->getId();
                    }

                    if(!$parentVisible && !$productVisible){
                        $productsSortedOut++;
                        continue;
                    }
                    
                } elseif(!$productVisible) {
                    $productsSortedOut++;
                    continue;
                }

                
                // check stock status just for simple products
                if((!$ignoreOutOfStockStatus || !$ignoreQuantity) && $mageProduct->getTypeId() == 'simple') {

                    $stock = $this->mageStockItem->get($product->getId());
                    // check stock status
                    if(!$ignoreOutOfStockStatus && $stock->getIsInStock()){
                        $productsSortedOut++;
                        continue;
                    }

                    // check stock quantity
                    if(!$ignoreQuantity && !$stock->getQty()){
                        $productsSortedOut++;
                        continue; 
                    }
                }

                $this->_sxUploader->addProduct($mageProduct, $this->_sxTransformerArgs);

            }

            //increase collected products (with products that has not been sent)
            if($productsSortedOut){
                $this->_sxUploader->getStatus()->increaseNumberOfSortedOut($productsSortedOut);
            }

            $startUploading = $productCounter < $collectBatchSize;

            // if ready, start uploading
            if ($startUploading) {

                $productsSortedOut = $this->_sxUploader->getStatus()->getNumberOfSortedOut();
                $productsPrepared = $this->_sxUploader->getStatus()->getNumberOfCollected() - $productsSortedOut;

                $this->_sxLogger->log("$productsPrepared products prepared for upload, $productsSortedOut products sorted out");

                $response = $this->_sxUploader->startUploading();

                if (isset($response['status']) && $response['status'] !== 'success') {
                    $this->_sxLogger->log($response['status'] . ' - ' . $response['message'], 'error');
                } 
            }

        } else {

            // uploading

            // continue uploading...
            $response = $this->_sxUploader->sendUploadBatch(true);

            
            if (isset($response['validation'][0]['schemaErrors'][0])) {
                $this->_sxLogger->log(json_encode($response), 'error');
            }

            if (isset($response['status']) && $response['status'] !== 'success') {
                $this->_sxLogger->log(json_encode($response), 'error');
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
            $this->_sxLogger->log(json_encode($response), 'error');
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
            $this->_sxLogger->log("$productCounterTotal products added to update-queue", 'success');
        }
        
    }

    /**
     * send single update product queue
     */
    public function sendProductUpdates()
    {
        if($this->_sxUpdater->sendUploadBatch()){
            $this->_sxLogger->log("product updates from queue sent", 'success');
        }

    }

}