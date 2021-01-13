<?php

namespace Semknox\Productsearch\Observer;

use Magento\Framework\Event\ObserverInterface;
use Semknox\Productsearch\Helper\SxHelper;
use Semknox\Productsearch\Controller\UploadControllerFactory;
use Semknox\Core\SxCore;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class ProductUpdate implements ObserverInterface
{
    public function __construct(
        SxHelper $sxHelper,
        UploadControllerFactory $uploadControllerFactoy,
        CollectionFactory $collectionFactory,
        Status $productStatus,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->_sxHelper = $sxHelper;
        $this->_uploadControllerFactoy = $uploadControllerFactoy;
        $this->_collectionFactory = $collectionFactory;
        $this->_productStatus = $productStatus;

        $this->logger = $logger;
        $this->productRepository = $productRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $sxShopUloads = array();

        // get shops
        foreach ($this->_sxHelper->getShopConfigs() as $key => $shopConfig) {

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


        // add product to update queue
        foreach ($sxShopUloads as $key => $shopUploader) {

            // add product to update
            $shopUploader->addProductUpdates($productCollection);
        }
    }
}
