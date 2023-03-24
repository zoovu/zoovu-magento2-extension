<?php

namespace Semknox\Productsearch\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ProductSyncNote extends Field
{

    /**
     * Render element html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $message = __("Product synchronisation can only be configured in the highest scope.<br><b>Use the button to change the Scope and access the configuration.</b>");
        $info = '<div id="messages"><div class="messages"><div class="message message-notice notice"><div data-ui-id="messages-message-notice">'.$message.'</div></div></div></div>';
        
        $button = $this->getProductSyncButtonHtml();

        return sprintf(
            '<tr class="system-fieldset-sub-head" id="row_%s"><td colspan="5"><p>%s</p><p>%s</p></td></tr>',
            $element->getHtmlId(),
            $info,
            $button
        );
    }

    private function getProductSyncButtonHtml()
    {
        // get default Scope Url
        $link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        foreach(["/section/semknox_productsearch_sync/store/", "/section/semknox_productsearch_sync/website/"] as $needle){
            $tmp = explode($needle, $link, 2);
            if(count($tmp) == 2){
                $link = $tmp[0] . "/section/semknox_productsearch_sync/";
            }
        }

        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'product sync',
                'label' => __('Configure product synchronisation'),
                'class' => 'primary',
                'type' => 'button',
                'onclick' => "window.open('$link','_self')"
            ]
        );
        return $button->toHtml();
    }

}
?>