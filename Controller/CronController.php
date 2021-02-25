<?php

namespace Semknox\Productsearch\Controller;

use Semknox\Productsearch\Helper\SxHelper;
use Semknox\Productsearch\Controller\UploadControllerFactory;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

class CronController 
{

    private $_sxHelper; 

    public function __construct(
        SxHelper $sxHelper,
        UploadControllerFactory $uploadControllerFactoy,
        DateTime $dateTime,
        DateTimeFactory $dateTimeFactory,
        TimezoneInterface $timezoneInterface
    ){
        $this->_sxHelper = $sxHelper;
        $this->_uploadControllerFactoy = $uploadControllerFactoy;
        $this->_dateTimeFactory = $dateTimeFactory;
        $this->_timezoneInterface = $timezoneInterface;


        $dateTime = $this->_timezoneInterface->date();
        $this->_currentMinute = (int) $dateTime->format('i');
        $this->_currentHour = (int) $dateTime->format('G');

    }


    public function cronRunner()
    {
        $startTime = time(); // for logging duration
        $flags = array();

        $sxUpload = $this->_uploadControllerFactoy->create();

        $sxShopUloads = array();

        foreach($this->_sxHelper->getShopConfigs() as $key => $shopConfig){

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
                $flags['running'] = true; // needed for queue action desicion
            } elseif ($shopUploader->isRunning()) {
                $flags['running'] = true; // needed for queue action desicion
            }
        }

        // [-2-] check queue actions update
        // ... later
        if(!isset($flags['running']) || !$flags['running']){
            // go on with single updates
            foreach ($sxShopUloads as $key => $shopUploader) {
                $shopUploader->sendProductUpdates();
                continue; // (always just one job per cronrun!)
            }

            // stop cron runner
            return $this;
        }


        // [-3-] collecting for all shops
        // >>> check if !!!COLLECTING!!! needs to be continued (always just one job per cronrun!)
        foreach ($sxShopUloads as $key => $shopUploader) {

            if ($shopUploader->isReadyToUpload()) continue;

            // !!!COLLECTING!!!
            $shopUploader->continueFullUpload();
            $flags['collecting'] = true;
            break; // (always just one job per cronrun!)
        }


        // [-4-] uploading for all shops
        // >>>> check if !!!UPLOADING!!! needs to be continued (always just one job per cronrun!)
        if (!isset($flags['collecting'])) {

            foreach ($sxShopUloads as $key => $shopUploader) {

                if (!$shopUploader->isReadyToUpload() || $shopUploader->isReadyToFinalize()) continue;

                // !!!UPLOADING!!!
                $shopUploader->continueFullUpload();
                $flags['uploading'] = true;
                break; // (always just one job per cronrun!)

            }
        }


        // [-5-] finalizing for all shops !!!AT ONCE!!!
        // >>> check if !!!FINALIZE UPLOADING!!! needs to be continued (always just one job per cronrun!)
        if (!isset($flags['collecting']) && !isset($flags['uploading'])) {

            $signalSent = true;

            foreach ($sxShopUloads as $key => $shopUploader) {

                if (!$shopUploader->isReadyToUpload() || !$shopUploader->isReadyToFinalize()) continue;

                // !!!FINALIZE UPLOADING!!!
                $shopUploader->finalizeFullUpload($signalSent);

                if (isset($shopUploader->config['userGroup'])) {
                    $signalSent = false;
                };
            }
        }

        return $this;

    }
}