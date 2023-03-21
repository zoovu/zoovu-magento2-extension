<?php

namespace Semknox\Productsearch\Model\Config\Source;

use Semknox\Productsearch\Helper\SxHelper;

class FrontendOutputType implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        SxHelper $sxHelper
    ) {
        $this->_sxHelper = $sxHelper;
    }

    /**
     * Return array of options
     * 
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            '0' => 'Magento Search integration',
        ];

        foreach($this->_sxHelper->getJsSearchConfigIds() as $configId){
            $options[$configId] = "SiteSearch JS-Search (ID: $configId)";
        }

        return $options;
    }
}