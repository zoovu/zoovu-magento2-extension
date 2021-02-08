<?php

namespace Semknox\Productsearch\Model\Amasty\Shopby;

use \Amasty\Shopby\Model\Request as AmastyRequest;

if (class_exists('Request')) {
    class AmastyRequestBridge extends AmastyRequest
    {
    }
} else {
    class AmastyRequestBridge
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
        return [];
    } 
}
