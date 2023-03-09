<?php

namespace Semknox\Productsearch\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Semknox\Productsearch\Helper\SxHelper;

class FrontendOutputType extends Field
{
    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        SxHelper $sxHelper,
        array $data = []
    ) {
        $this->_sxHelper = $sxHelper;
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if(!$this->_sxHelper->get('sxProjectId') || !$this->_sxHelper->get('sxApiKey')) return '';

        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        $element->setData('label', '');
        return parent::render($element);
    }

}
?>