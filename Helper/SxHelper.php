<?php

namespace Semknox\Productsearch\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\DirectoryList;

use Psr\Log\LoggerInterface;


class SxHelper extends AbstractHelper
{

    protected $_sxFolder = "semknox/";

    protected $_sxUploadBatchSize = 200;
    protected $_sxCollectBatchSize = 100;
    protected $_sxRequestTimeout = 15;

    protected $_sxSandboxApiUrl = "https://stage-oxid-v3.semknox.com/";
    protected $_sxApiUrl = "https://api-oxid-v3.semknox.com/";

    protected $_sxMasterConfig = false;
    protected $_sxMasterConfigPath = "masterConfig%s.json";

    protected $_sxDeleteQueuePath = "delete-queue/";
    protected $_sxUpdateQueuePath = "update-queue/";


    public function __construct(
        ScopeConfigInterface $scopeConfig, 
        LoggerInterface $logger,
        DirectoryList $dir
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_logger = $logger;
        $this->_dir = $dir;

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

            if ($value) return $value;
        }

        // check preset values or take default
        $sxKey = '_' . $key;

        return isset($this->$sxKey)
            ? $this->$sxKey
            : $default;
          
    }


    public function log($message)
    {
        // todo: improve
        $this->_logger->info($message);
    }

}