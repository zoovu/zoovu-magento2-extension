<?php

namespace Semknox\Productsearch\Model\Amasty\Shopby;

if (class_exists('\Amasty\Shopby\Model\Request')) {
    class AmastyRequestBridge extends \Amasty\Shopby\Model\Request
    {
    }
} else {
    class AmastyRequestBridge extends \Magento\Framework\DataObject
    {
    }
}

class Request extends AmastyRequestBridge
{
    /**
     * @param $filter
     * @return mixed|string
     */

    public function getFilterParam($filter = null)
    {
        if (!$filter) return '';

        return parent::getFilterParam($filter);
    }
}
