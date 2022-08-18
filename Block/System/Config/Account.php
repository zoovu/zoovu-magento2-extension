<?php

namespace Semknox\Productsearch\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Semknox\Productsearch\Helper\SxHelper;

class Account extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Semknox_Productsearch::system/config/account.phtml';

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
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        $element->setData('label','');

        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getAccountData()
    {
        return [
            'extensionVersion' => $this->_sxHelper->getExtensionVersion(),
            'shopSystemVersion' => $this->_sxHelper->getSystemVersion()
        ];

    }

    /**
     * Generate collect button html
     *
     * @return string
     */
    public function getAccountHtml()
    {
        $createButton = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button')->setData(
            [
                'id' => 'create_account_button',
                'label' => __('Create SS360 project for this Store View'),
            ]
        );

        $loginButton = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'login_button',
                'label' => __('Login with existing SS360 Project'),
                'class' => 'primary'
            ]
        );

        return $createButton->toHtml(). $loginButton->toHtml();
    }
}
?>