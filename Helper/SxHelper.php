<?php

namespace Semknox\Productsearch\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

use Semknox\Core\Services\Search\Sorting\SortingOption;

class SxHelper extends AbstractHelper
{

    protected $_sxFolder = "var/semknox/";

    protected $_sxUploadBatchSize = 200;
    protected $_sxCollectBatchSize = 100;
    protected $_sxRequestTimeout = 15;

    protected $_sxSandboxApiUrl = "https://stage-oxid-v3.semknox.com/";
    protected $_sxApiUrl = "https://api-oxid-v3.semknox.com/";

    protected $_sxMasterConfig = false;
    protected $_sxMasterConfigPath = "masterConfig%s.json";

    protected $_sxDeleteQueuePath = "delete-queue/";
    protected $_sxUpdateQueuePath = "update-queue/";


    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;

        /*
        $this->_oxRegistry = new Registry();
        $this->_oxConfig = $this->_oxRegistry->getConfig();

        $workingDir = $this->_oxConfig->getLogsDir().'../'. $this->_sxFolder;

        //$this->_sxFolder = $logsDir . $this->_sxFolder;

        $this->_sxMasterConfigPath = $workingDir . $this->_sxMasterConfigPath;

        $this->_sxDeleteQueuePath = $workingDir . $this->_sxDeleteQueuePath;
        $this->_sxUpdateQueuePath = $workingDir . $this->_sxUpdateQueuePath;
        */

    }


    /**
     * Get a value
     *
     * @param $key
     * @param null $default
     *
     * @return mixed|null
     */
    public function get($key, $default = null)
    {

        $value = $this->scopeConfig->getValue(
            'semknox_productsearch/semknox_productsearch_settings/'.$key,
            ScopeInterface::SCOPE_STORE
        );
        if ($value) return $value;

        // check preset values or take default
        $sxKey = '_' . $key;

        return isset($this->$sxKey)
            ? $this->$sxKey
            : $default;
            
    }



}