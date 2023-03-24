<?php

namespace Semknox\Productsearch\Helper;

use Laminas\Validator\Explode;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Semknox\Productsearch\Helper\SxLogger;

class SxHelper extends AbstractHelper
{

    protected $_sxFolder = "semknox/";

    protected $_sxUploadBatchSize = 1000;
    protected $_sxCollectBatchSize = 250;
    protected $_sxRequestTimeout = 15;
    protected $_sxRequestTimeoutFrontend = 5;

    protected $_sxSandboxApiUrl = "https://api-magento.sitesearch360.com/";
    protected $_sxApiUrl = "https://api-magento.sitesearch360.com/";
    protected $_jsSearchConfigUrl = "https://api.sitesearch360.com/plugin?token=%s";
    protected $_jsSearchFrontendUrl = "https://js.sitesearch360.com/plugin/bundle/%s.js";

    protected $_sxMasterConfig = false;
    protected $_sxMasterConfigPath = "masterConfig%s.json";

    protected $_sxDeleteQueuePath = "delete-queue/";
    protected $_sxUpdateQueuePath = "update-queue/";


    protected $_sxShopConfigs = [];

    public function __construct(
        ScopeConfigInterface $scopeConfig, 
        DirectoryList $dir,
        StoreManagerInterface $storeManagerInterface,
        Http $request,
        ProductMetadataInterface $productMetadata,
        ModuleListInterface $moduleList,
        SxLogger $sxLogger
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_dir = $dir;
        $this->_storeManager = $storeManagerInterface;
        $this->_request = $request;
        $this->productMetadata = $productMetadata;
        $this->_moduleList = $moduleList;
        $this->_logger = $sxLogger;

        $this->_sxFolder = $this->_dir->getPath('var') . '/' . $this->_sxFolder;

        // Quick fix
        if(!is_dir($this->_sxFolder)){
            mkdir($this->_sxFolder);
        }

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
        if(!$storeId){
            $storeId = (int) $this->_request->getParam('store', 0);
        }

        $configGroupsToCheck = [
            'semknox_productsearch_general/semknox_productsearch_settings/',
            'semknox_productsearch_general/semknox_productsearch_advanced/', 
            'semknox_productsearch_sync/semknox_productsearch_cronjob/',
            'semknox_productsearch_sync/semknox_productsearch_sync/',

            // fallback for older extension versions
            'semknox_productsearch/semknox_productsearch_settings/',
            'semknox_productsearch/semknox_productsearch_cronjob/',
            'semknox_productsearch_general/semknox_productsearch_settings/',
            'semknox_productsearch_scheduler/semknox_productsearch_cronjob/'
        ];

        foreach($configGroupsToCheck as $group){
            $value = $this->_scopeConfig->getValue(
                $group . $key,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );

            if ($value === '0' || $value){
                return trim($value);
            } 

        }

        // check preset values or take default
        $sxKey = '_' . $key;

        return isset($this->$sxKey)
            ? $this->$sxKey
            : $default;
          
    }


    public function log($message, $logLevel = 'info')
    {
        $this->_logger->log($message, $logLevel);
    }

    
    public function getStoreIdentifier($store=null)
    {
        if (!$store) {
            $store = $this->_storeManager->getStore();
        }

        $storeId = $store->getId();
        return $storeId . '-' . $store->getCode();
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

            $storeIdentifier = $this->getStoreIdentifier($store);

            if (!empty($this->_sxShopConfigs) && isset($this->_sxShopConfigs[$storeIdentifier])){
                continue;
            }

            if($storeConfig = $this->getConfig($store)){
                $sxShopConfigs[$storeIdentifier] = $storeConfig;
            }

        }

        $this->_sxShopConfigs = $sxShopConfigs;

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

        $storeIdentifier = $this->getStoreIdentifier($store);

        if(!empty($this->_sxShopConfigs) && isset($this->_sxShopConfigs[$storeIdentifier]))
        {
            return $this->_sxShopConfigs[$storeIdentifier];
        }

        $storeId = $store->getId();
        $projectId = $this->get('sxProjectId', $storeId, null);
        $apiKey = $this->get('sxApiKey', $storeId, null);

        if(!$projectId || !$apiKey){
            $this->_sxShopConfigs[$storeIdentifier] = false;
            return false;
        }

        $currentShopConfig = [
            'projectId' => $projectId,
            'apiKey' => $apiKey,

            /*
            'sandbox' => $this->get('sxIsSandbox', $storeId),
            'apiUrl' => $this->get('sxIsSandbox', $storeId) ? $this->get('sxSandboxApiUrl', $storeId) : $this->get('sxApiUrl', $storeId),
            */
            'sandbox' => false,
            'apiUrl' => $this->get('sxApiUrl', $storeId),
            
            
            'shopId' => $storeId,
            'cronjobHour' => $this->getSxCronjobHour($storeId),
            'cronjobMinute' => $this->getSxCronjobMinute($storeId),
            'collectBatchSize' => (int) $this->get('sxCollectBatchSize', $storeId),
            'uploadBatchSize' => (int) $this->get('sxUploadBatchSize', $storeId),

            // Ticket 2021-03-26: timeout min. 5s
            'requestTimeout' => ((int) $this->get('sxRequestTimeout', $storeId) >= 5) ?  (int) $this->get('sxRequestTimeout', $storeId) : 5,
            'requestTimeoutFrontend' => ((int) $this->get('sxRequestTimeoutFrontend', $storeId) >= 5) ? (int) $this->get('sxRequestTimeoutFrontend', $storeId) : 5,

            'removeFromImageUrl' => $this->get('sxRemoveFromImageUrl', $storeId,''),

            'storeIdentifier' => $storeIdentifier,
            'storeRootCategory' => $store->getRootCategoryId(),

            // shopsystem settings
            'sxFrontendActive' => $this->get('sxFrontendActive', $storeId, 1),
            'sxFrontendOutputType' => $this->get('sxFrontendOutputType', $storeId, 0),
            'sxUploadActive' => $this->get('sxUploadActive', $storeId, 1),
            'sxIncrementalUpdatesActive' => $this->get('sxIncrementalUpdatesActive', $storeId, 1),
            'sxAnswerActive' => $this->get('sxAnswerActive', $storeId, 1),

            // current shopsystem 
            'shopsystem' => 'MAGENTO',
            // current shopsystem version
            'shopsystemversion' => $this->productMetadata->getVersion(),
            // current extension version
            'extensionversion' => $this->getExtensionVersion(),
 

        ];

        $jsSearchConfigId = $this->get('sxFrontendOutputType', $storeId, 0);
        if($jsSearchConfigId != 0){
            $currentShopConfig['jsSearchUrl'] = sprintf($this->_jsSearchFrontendUrl, $jsSearchConfigId);
        }

        $this->_sxShopConfigs[$storeIdentifier] = $currentShopConfig;

        return $currentShopConfig;
    }

    protected function getSxCronjobHour($storeId)
    {
        $fallback = (int) $this->get('sxCronjobHour', $storeId, "02"); // Compatibility with previous versions
        
        $cronjobTime = $this->get('sxCronjobTime', $storeId, "$fallback,00,00");
        return (int) explode(',', $cronjobTime,3)[0];
    }

    protected function getSxCronjobMinute($storeId)
    {
        $fallback = (int) $this->get('sxCronjobMinute', $storeId, "00"); // Compatibility with previous versions

        $cronjobTime = $this->get('sxCronjobTime', $storeId, "00,$fallback,00");
        return (int) explode(',', $cronjobTime, 3)[1];
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
            if(stripos($param,'sx_') === 0 && $value){
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


    public function isSxSearchFrontendActive()
    {
        $setActive = is_array($this->getConfig()) && isset($this->getConfig()['sxFrontendActive']) && $this->getConfig()['sxFrontendActive'] > 0;

        $isReachable = (isset($this->isSxSearchActive)) ? $this->isSxSearchActive : true;

        return $setActive && $isReachable;
    }

    public function isSxAnswerActive()
    {
        return is_array($this->getConfig()) && isset($this->getConfig()['sxAnswerActive']) && $this->getConfig()['sxAnswerActive'] > 0;
    }

    public function sxUploadProductsWithStatusOutOfStock()
    {
        return is_array($this->getConfig()) && isset($this->getConfig()['sxUploadProductsWithStatusOutOfStock']) && $this->getConfig()['sxUploadProductsWithStatusOutOfStock'] > 0;
    }

    public function sxUploadProductsWithZeroQuantity()
    {
        return is_array($this->getConfig()) && isset($this->getConfig()['sxUploadProductsWithZeroQuantity']) && $this->getConfig()['sxUploadProductsWithZeroQuantity'] > 0;
    }

    public function getExtensionVersion()
    {
        return $this->_moduleList->getOne('Semknox_Productsearch')['setup_version'];
    }

    public function getSystemVersion()
    {
        return $this->productMetadata->getVersion();
    }

    public function getSystemEmail()
    {
        return $this->_scopeConfig->getValue('trans_email/ident_general/email', ScopeInterface::SCOPE_STORE);
    }

    public function getSystemCompanyName()
    {
        $value = $this->_scopeConfig->getValue('general/store_information/name', ScopeInterface::SCOPE_STORE);
        if(!$value){
            $value = $this->getSystemProjectUrl();
        }
        return $value;
    }

    public function getSystemStoreName($store=null)
    {
        if(!$store){
            $store = $this->_storeManager->getStore();
        }
        return $store->getName();
    }

    public function getSystemProjectUrl()
    {
        return $this->_scopeConfig->getValue('web/secure/base_url', ScopeInterface::SCOPE_STORE);
    }

    public function getSystemLanguage()
    {
        $isoCode = $this->_scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE);
        return explode('_', $isoCode,2)[0];
    }

    public function getJsSearchConfigIds()
    {
        $token = $this->get('sxApiKey');
        $url = sprintf($this->_jsSearchConfigUrl, $token);

        try {
            $response = file_get_contents($url);
        } catch (\Exception $e) {
            $response = false;
        }

        if($response === false) return [];

        $response = \json_decode($response, true);

        if($response && isset($response['status']) && isset($response['ids']) && $response['status'] == 'success'){
            return $response['ids'];
        }

        return [];
    }
}