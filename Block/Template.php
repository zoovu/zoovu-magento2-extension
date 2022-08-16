<?php

namespace Semknox\Productsearch\Block;

use Semknox\Productsearch\Helper\SxHelper;
use Magento\Framework\Module\ModuleListInterface;

class Template extends \Magento\Framework\View\Element\Template
{
    private $_sxConfig;

    public function __construct(
        SxHelper $sxHelper,
        ModuleListInterface $moduleList,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Locale\Resolver $store,
        array $data = []
    ) {
        $this->_sxHelper = $sxHelper;
        $this->_moduleList = $moduleList;
        $this->_store = $store;

        parent::__construct($context, $data);
    }

    public function getConfigValue($key)
    {
        if(!is_array($this->_sxConfig)){
            $this->_sxConfig = $this->_sxHelper->getConfig();
        }

        if(!$this->_sxConfig) return '';

        return isset($this->_sxConfig[$key]) ? $this->_sxConfig[$key] : '';
        
    }

    public function getCurrentLanguage()
    {
        $fullLanguageCode = $this->_store->getLocale() ?? 'en';
        $language = explode('_', $fullLanguageCode,2);
        return $language[0];
    }

    public function getExtensionVersion()
    {
        return $this->_sxHelper->getExtensionVersion();
    }

}