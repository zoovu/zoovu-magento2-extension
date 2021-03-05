<?php

namespace Semknox\Productsearch\Observer;

use Magento\Framework\Event\ObserverInterface;
use Semknox\Productsearch\Helper\SxHelper;
use Semknox\Productsearch\Controller\UploadControllerFactory;
use Semknox\Core\SxCore;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;

class ProductUpdate implements ObserverInterface
{
    public function __construct(
        SxHelper $sxHelper,
        UploadControllerFactory $uploadControllerFactoy,
        CollectionFactory $collectionFactory,
        Status $productStatus,
        Visibility $productVisibility,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->_sxHelper = $sxHelper;
        $this->_uploadControllerFactoy = $uploadControllerFactoy;
        $this->_collectionFactory = $collectionFactory;
        $this->_productStatus = $productStatus;
        $this->_productVisibility = $productVisibility;

        $this->logger = $logger;
        $this->productRepository = $productRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $sxShopUloads = array();

        // get shops
        foreach ($this->_sxHelper->getShopConfigs() as $key => $shopConfig) {

            if (!$shopConfig['sxUploadActive'] || !$shopConfig['sxIncrementalUpdatesActive']) continue;

            $uploadController = $this->_uploadControllerFactoy->create();
            $uploadController->setConfig($shopConfig);

            $sxShopUloads[$key] = $uploadController;
            $sxShopUloads[$key]->config = $shopConfig;
        }

        if(!count($sxShopUloads)) return;


        // get product collection
        $_product = $observer->getEvent()->getProduct();
        $_productId = $_product->getId();

        $productCollection = $this->_collectionFactory->create();
        $productCollection->addFieldToFilter(
            'entity_id',
            array(
                'in' => [$_productId]
            )
        );
        $productCollection->addAttributeToFilter('status', ['in' => $this->_productStatus->getVisibleStatusIds()]);
        $productCollection->setVisibility($this->_productVisibility->getVisibleInSearchIds());

        if ($this->_sxHelper->sxUploadProductsWithStatusOutOfStock()) {
            $productCollection->setFlag('has_stock_status_filter', false);
        }

        if (!$this->_sxHelper->sxUploadProductsWithZeroQuantity()) {
            $productCollection->joinField('qty', 'cataloginventory_stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left');
            $productCollection->addAttributeToFilter('qty', ['gt' => 0]);
        }

        // add product to update queue
        foreach ($sxShopUloads as $key => $shopUploader) {

            // add product to update
            $shopUploader->addProductUpdates($productCollection);
        }
    }
}
