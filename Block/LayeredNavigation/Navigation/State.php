<?php

namespace Semknox\Productsearch\Block\LayeredNavigation\Navigation;

use Semknox\Productsearch\Helper\SxHelper;

class State extends \Magento\LayeredNavigation\Block\Navigation\State
{

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param array $data
     */
    public function __construct(
        SxHelper $sxHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        array $data = []
    ) {
        $this->_sxHelper = $sxHelper;
        parent::__construct($context, $layerResolver, $data);
    }


    /**
     * Retrieve Clear Filters URL
     *
     * @return string
     */
    public function getClearUrl()
    {
        if (!$this->_sxHelper->isSxSearchFrontendActive()) return parent::getClearUrl();

        $filterState = [];

        foreach ($this->getActiveFilters() as $item) {
            $filterState[$item->getRequestVar()] = $item->getCleanValue();
        }

        $params['_current'] = true;
        $params['_use_rewrite'] = true;
        $params['_query'] = $filterState;
        $params['_escape'] = true;
        return $this->_urlBuilder->getUrl('*/*/*', $params);
    }


}
