<?php

namespace Semknox\Productsearch\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;

class GeneralSettingsNote extends Field
{

    /**
     * Render element html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $message = __("The search is configured separately in detail for each store view. <b>Please switch to a store view to enter your access data, for example.</b>");
        $info = '<div id="messages"><div class="messages"><div class="message message-notice notice"><div data-ui-id="messages-message-notice">'.$message.'</div></div></div></div>';

        return sprintf(
            '<tr class="system-fieldset-sub-head" id="row_%s"><td colspan="5"><p>%s</p></td></tr>',
            $element->getHtmlId(),
            $info
        );
    }

}
?>