<?php

namespace Semknox\Productsearch\Block;

use Semknox\Productsearch\Helper\SxHelper;

class Template extends \Magento\Framework\View\Element\Template
{
    private $_sxConfig;

    public function __construct(
        SxHelper $sxHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->_sxHelper = $sxHelper;

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

}