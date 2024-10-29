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
use Magento\Framework\App\ResourceConnection;

class UploadController {

   
    private $_sxHelper, $_sxCore, $_sxConfig, $_sxUploader, $_sxUpdater, $_sxLogger, $appEmulation;

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
        Status $productStatus,
        ResourceConnection $resourceConnection
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
        $this->resourceConnection = $resourceConnection;

        $this->startTime = microtime(true); 
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
                'sxHelper' =>  $this->_sxHelper,
                'resourceConnection' => $this->resourceConnection
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
        $productCollection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

        // getting media gallery here destroys limit/offset. 
        //$productCollection->addMediaGalleryData();
            
        if ($storeId) {
            $this->appEmulation->stopEnvironmentEmulation();
        }

        return $productCollection;
    }


    /**
     * continue running product upload
     * 
     */
    public function continueFullUpload()
    {
        $maxTime = $this->_sxHelper->get('sxMaxExecutionTime', $this->_sxConfig->get('shopId'), 45); // seconds 
        $maxTimeBreak = $maxTime - (0.1 * $maxTime);

        $maxMemory = $this->_sxHelper->get('sxMemoryLimit', $this->_sxConfig->get('shopId'), 256); // MB
        $maxMemoryBreak =  $maxMemory - (0.1 * $maxMemory);

        if ($this->_sxUploader->isCollecting()) {

            $startUploading = false;

            // collecting
            $storeId = $this->_sxConfig->get('shopId');
            $collectBatchSize = $this->_sxConfig->get('collectBatchSize');

            $productCollection = $this->getUploadProductCollection($storeId);

            
            $currentPage = floor((int) ($this->_sxUploader->getNumberOfCollected() / $collectBatchSize)) + 1;
            /*
            $productCollection->setPageSize($collectBatchSize);
            $productCollection->setCurPage($currentPage);
            */

            $offset = $this->_sxUploader->getNumberOfCollected();
            $productCollection->getSelect()->limit($collectBatchSize, $offset);

            $ignoreQuantity = $this->_sxHelper->sxUploadProductsWithZeroQuantity();
            $ignoreOutOfStockStatus = $this->_sxHelper->sxUploadProductsWithStatusOutOfStock();

            $cachedParents = [];

            $productCounter = 0;
            $continue = false;

            // to check query
            //$this->_sxLogger->log($productCollection->getSelect()->__toString(), 'success');

            foreach ($productCollection as $mageProduct) {

                if ($continue) continue;

                // we need to load product here, because we cant load mediagallery in getUploadProductCollection anymore
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $mageProduct = $objectManager->create('Magento\Catalog\Model\Product')->load($mageProduct->getId());

                if ($storeId) {
                    $mageProduct->setStoreId($storeId);
                }

                $memoryUsage = memory_get_usage();
                $memoryUsage = round($memoryUsage/1048576,2); // in MB

                $runningTime = round(microtime(true) - $this->startTime);

                if($memoryUsage >= $maxMemoryBreak || $runningTime > $maxTimeBreak){

                    $msg = "limit reached [Memory: ".$memoryUsage."MB / ".$maxMemory."MB | ExecutionTime: ".$runningTime."s / ".$maxTime."s]";
                    $msg .= " => collected $productCounter products instead of $collectBatchSize";

                    $logLevel = $productCounter ? 'info' : 'error';

                    $this->_sxLogger->log($msg, $logLevel);

                    if($productCounter){
                        $this->_sxLogger->log("=> No products will be lost! This is just an info that your configuration could be optimised", $logLevel);
                    } else {
                        $this->_sxLogger->log("=> Your configuration must be optimised!", $logLevel);
                    }

                    //return
                    $continue = true;
                    continue;
                }

                $productCounter++;

                // get parent if is child of configurable
                $parentId = $this->mageConfigurableProduct->getParentIdsByChild($mageProduct->getId());

                $sxGroupIdentifier = array_shift($parentId) ?? false;
                $mageProduct->setData('sxGroupIdentifier', $sxGroupIdentifier);

                $parent = false;
                if($sxGroupIdentifier ){

                    if(!array_key_exists($sxGroupIdentifier,$cachedParents)){
                        $cachedParents[$sxGroupIdentifier] = $this->_productRepository->getById($sxGroupIdentifier, false, $storeId) ?? false;
                    }
                    $parent = $cachedParents[$sxGroupIdentifier];
                }


                // check visibility
                $productVisible = $mageProduct->getVisibility() >= 3;

                $mageProduct->setData('sxProductUrl', $mageProduct->getUrlModel()->getProductUrl($mageProduct, ['_escape' => true]));
        
                if($parent){

                    $mageProduct->setData('sxParentName',(string) $parent->getName());

                    // if simple product VISIBLE, send it not as part of the parent
                    if($productVisible){
                        $mageProduct->setData('sxGroupIdentifier', $mageProduct->getId());
                    } else {
                        $mageProduct->setData('sxProductUrl', $parent->getUrlModel()->getProductUrl($parent, ['_escape' => true]));
                    }

                    $parentVisible = $parent->getVisibility() >= 3;
                    // if simple product AND parent NOT VISIBLE, do not send at all
                    if(!$parentVisible && !$productVisible){
                        $this->_sxUploader->getStatus()->increaseNumberOfSortedOut();
                        continue;
                    }

                    // ELSE
                    // if parent VISIBLE but simple product NOT VISIBLE, send simple product es part of parent
                    // -> done in the upper part!
                    
                } elseif(!$productVisible) {
                    $this->_sxUploader->getStatus()->increaseNumberOfSortedOut();
                    continue;
                }

                
                // check stock status just for simple products
                if((!$ignoreOutOfStockStatus || !$ignoreQuantity) && $mageProduct->getTypeId() == 'simple') {

                    try {
                        // try, because: if article does not have "stock-article" 
                        $stock = $this->mageStockItem->get($mageProduct->getId());

                        // check stock status
                        if (!$ignoreOutOfStockStatus && !$stock->getIsInStock()) {
                            $this->_sxUploader->getStatus()->increaseNumberOfSortedOut();
                            continue;
                        }

                        // check stock 
                        if (!$ignoreQuantity && !$stock->getQty()) {
                            $this->_sxUploader->getStatus()->increaseNumberOfSortedOut();
                            continue;
                        }

                    } catch (\Exception $e) {
                        // do nothing
                    }

                }

                $this->_sxUploader->addProduct($mageProduct, $this->_sxTransformerArgs);

            }

            //$this->_sxLogger->log("productCounter: $productCounter, collectBatchSizte: $collectBatchSize, currentPage: $currentPage, offset: $offset", 'success');

            $startUploading = $productCounter < $collectBatchSize;

            // if ready, start uploading
            if ($startUploading) {

                $this->_sxUploader->startUploading();
            }

        } else {

            // uploading

            // continue uploading...
            $this->_sxUploader->sendUploadBatch(true);
        }

    }

    /**
     * finalize running product upload
     * 
     */
    public function finalizeFullUpload($signalApi = true)
    {
        $response = $this->_sxUploader->finalizeUpload($signalApi);
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