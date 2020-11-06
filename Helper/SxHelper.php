<?php

namespace Semknox\Productsearch\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;

use Psr\Log\LoggerInterface;


class SxHelper extends AbstractHelper
{

    protected $_sxFolder = "semknox/";

    protected $_sxUploadBatchSize = 1000;
    protected $_sxCollectBatchSize = 500;
    protected $_sxRequestTimeout = 15;

    protected $_sxSandboxApiUrl = "https://stage-magento-v3.semknox.com/";
    protected $_sxApiUrl = "https://api-magento-v3.semknox.com/";

    protected $_sxMasterConfig = false;
    protected $_sxMasterConfigPath = "masterConfig%s.json";

    protected $_sxDeleteQueuePath = "delete-queue/";
    protected $_sxUpdateQueuePath = "update-queue/";


    public function __construct(
        ScopeConfigInterface $scopeConfig, 
        LoggerInterface $logger,
        DirectoryList $dir,
        StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_logger = $logger;
        $this->_dir = $dir;
        $this->_storeManager = $storeManagerInterface;
        $this->_request = $request;

        $this->_sxFolder = $this->_dir->getPath('var') . '/' . $this->_sxFolder;

    }


    /**
     * Get a value
     *
     * @param $key
     * @param null $default
     *
     * @return mixed|null
     */
    public function get($key, $storeId = null, $default = null)
    {
        $configGroupsToCheck = [
            'semknox_productsearch/semknox_productsearch_settings/',
            'semknox_productsearch/semknox_productsearch_cronjob/'
        ];

        foreach($configGroupsToCheck as $group){
            $value = $this->_scopeConfig->getValue(
                $group . $key,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );

            if ($value) return trim($value);
        }

        // check preset values or take default
        $sxKey = '_' . $key;

        return isset($this->$sxKey)
            ? $this->$sxKey
            : $default;
          
    }


    public function log($message, $logLevel = 'info')
    {
        // todo: improve
        $this->_logger->info($message);
    }



    /**
     * get the configs of all shops
     * 
     * @return array
     */
    public function getShopConfigs()
    {
        $sxShopConfigs = array();

        foreach ($this->_storeManager->getStores() as $store) {

            if($storeConfig = $this->getConfig($store)){
                $storeIdentifier = $storeConfig['storeIdentifier'];
                $sxShopConfigs[$storeIdentifier] = $storeConfig;
            }

        }

        return $sxShopConfigs;
    }


    /**
     * get the config of one/current store
     * 
     * @return array
     */
    public function getConfig($store = null)
    {
        if(!$store){
            $store = $this->_storeManager->getStore();
        }

        $storeId = $store->getId();

        $projectId = $this->get('sxProjectId', $storeId, null);
        $apiKey = $this->get('sxApiKey', $storeId, null);

        if(!$projectId || !$apiKey) return false;

        $storeIdentifier = $storeId . '-' . $store->getCode();

        $currentShopConfig = [
            'projectId' => $projectId,
            'apiKey' => $apiKey,
            'sandbox' => $this->get('sxIsSandbox', $storeId),

            'apiUrl' => $this->get('sxIsSandbox', $storeId) ? $this->get('sxSandboxApiUrl', $storeId) : $this->get('sxApiUrl', $storeId),
            'shopId' => $storeId,
            'cronjobHour' => (int) $this->get('sxCronjobHour', $storeId),
            'cronjobMinute' => (int) $this->get('sxCronjobMinute', $storeId),

            'collectBatchSize' => (int) $this->get('sxCollectBatchSize', $storeId),
            'uploadBatchSize' => (int) $this->get('sxUploadBatchSize', $storeId),
            'requestTimeout' => (int) $this->get('sxRequestTimeout', $storeId),

            'storeIdentifier' => $storeIdentifier,
            'storeRootCategory' => $store->getRootCategoryId(),

            // shopsystem settings
            'sxFrontendActive' => $this->get('sxFrontendActive', $storeId, true),
            'sxUploadActive' => $this->get('sxUploadActive', $storeId, true),
            'sxIncrementalUpdatesActive' => $this->get('sxIncrementalUpdatesActive', $storeId, true),
            'sxAnswerActive' => $this->get('sxAnswerActive', $storeId, true),

        ];

        return $currentShopConfig;
    }


    public function setSxResponseStore($key, $value)
    {
        if(!$key || !$value) return; 
        $this->_sxResponseStore[$key] = $value;
    }

    public function getSxResponseStore($key, $default = false)
    {
        return isset($this->_sxResponseStore[$key]) ?  $this->_sxResponseStore[$key] : $default;
    }

    public function getSetFilters()
    {
        $filters = array();
        
        foreach($this->_request->getParams() as $param => $value)
        {
            if(stripos($param,'sx_') === 0){
                $param = urldecode($param);
                $filters[substr($param,3)] = $value;
            }
        }

        return $filters;
    }


    public function getSetOrder()
    {
        return $this->_request->getParam('product_list_order', false);
    }

    public function getSetDir()
    {
        return $this->_request->getParam('product_list_dir', 'desc');
    }
    
    public function isSearch()
    {
        return ($this->_request->getFullActionName() == 'catalogsearch_result_index');
    }


}