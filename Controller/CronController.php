<?php

namespace Semknox\Productsearch\Controller;

use Semknox\Productsearch\Helper\SxHelper;
use Semknox\Productsearch\Controller\UploadControllerFactory;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

class CronController 
{

    private $_sxHelper; 

    public function __construct(
        SxHelper $sxHelper,
        UploadControllerFactory $uploadControllerFactoy,
        DateTime $dateTime
    ){
        $this->_sxHelper = $sxHelper;
        $this->_uploadControllerFactoy = $uploadControllerFactoy;

        $this->_currentMinute = (int) $dateTime->gmtDate('i');
        $this->_currentHour = (int) $dateTime->gmtDate('G');
    }


    public function cronRunner()
    {
        $startTime = time(); // for logging duration
        $flags = array();

        $sxUpload = $this->_uploadControllerFactoy->create();

        $sxShopUloads = array();

        foreach($sxUpload->getShopConfigs() as $key => $shopConfig){

            $uploadController = $this->_uploadControllerFactoy->create();
            $uploadController->setConfig($shopConfig);

            $sxShopUloads[$key] = $uploadController;
            $sxShopUloads[$key]->config = $shopConfig;
            $sxShopUloads[$key]->start_upload = ($this->_currentHour == $shopConfig['cronjobHour'] && $this->_currentMinute == $shopConfig['cronjobMinute']);
        }


        // [-1-] Check if upload has to be started
        foreach ($sxShopUloads as $key => $shopUploader) {
            if ($shopUploader->start_upload && !$shopUploader->isRunning()) {
                $shopUploader->startFullUpload();
                unset($sxShopUloads[$key]);
                $flags['running'] = true;
            } elseif ($shopUploader->isRunning()) {
                $flags['running'] = true;
            }
        }

        // [-2-] check queue actions
        // ... later


        // [-3-] collecting for all shops
        // >>> check if !!!COLLECTING!!! needs to be continued (always just one job per cronrun!)
        foreach ($sxShopUloads as $key => $shopUploader) {

            if ($shopUploader->isReadyToUpload()) continue;

            // !!!COLLECTING!!!
            $shopUploader->continueFullUpload();
            $flags['collecting'] = true;
            break; // (always just one job per cronrun!)
        }





        return $this;

    }
}