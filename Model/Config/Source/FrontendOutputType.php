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

        // get all js configs:
        // todo:....
        $jsId = rand();
        $options[$jsId] = "JS-Search $jsId";

        return $options;
    }
}