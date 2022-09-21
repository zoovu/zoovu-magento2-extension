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
            'shopSystemVersion' => $this->_sxHelper->getSystemVersion(),
            'shopSystemEmail' => $this->_sxHelper->getSystemEmail(),
            'shopSystemCompany' => $this->_sxHelper->getSystemCompanyName(),
            'shopSystemProjectUrl' => $this->_sxHelper->getSystemProjectUrl(),
            'shopSystemLanguage'=> $this->_sxHelper->getSystemLanguage(),
            'shopSystemStoreName' => $this->_sxHelper->getSystemStoreName(),
            'shopSystemStoreIdentifier' => $this->_sxHelper->getStoreIdentifier()
        ];

    }

    public function getAccountButtonHtml()
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

    public function getBackendButtonHtml()
    {
        $currentConfig = $this->_sxHelper->getConfig();
        if(!isset($currentConfig['projectId'])) return '';

        $backendButton = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'backend_button',
                'label' => __('Open Site Search 360 Dashboard'),
                'class' => 'primary',
                'type' => 'button',
                'onclick' => "window.open('https://app.sitesearch360.com/dashboard/query-logs?projectId=". $currentConfig['projectId']."', '_blank')"
            ]
        );

        return $backendButton->toHtml();
    }


}
?>