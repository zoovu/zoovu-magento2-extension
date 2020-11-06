<?php

namespace Semknox\Productsearch\Model\Catalog\Layer;

use Semknox\Productsearch\Helper\SxHelper;
use Semknox\Productsearch\Block\LayeredNavigation\Navigation\FilterItemAdapterFactory;

class State extends \Magento\Catalog\Model\Layer\State
{

    public function __construct(
        array $data = [],
        SxHelper $sxHelper,
        \Magento\LayeredNavigation\Block\Navigation\FilterRendererFactory $filterRendererFactory,
        FilterItemAdapterFactory $filterItemAdapterFactory
    )
    {
        $this->_sxHelper = $sxHelper;  
        $this->_filterRenderer = $filterRendererFactory; 
        $this->_filterItemAdapter = $filterItemAdapterFactory;
        parent::__construct($data);
    }



    public function getFilters()
    {
        $filterList = [];

        foreach ($this->_sxHelper->getSxResponseStore('activeFilters', []) as $sxFilter) {

            $filter = $this->_filterRenderer->create();
            $filter->isActiveFilter = true;

            foreach($sxFilter['values'] as $value){

                $sxFilter['value'] = $value;
                $filter->setSxFilter($sxFilter);
                $filterList[] = $filter;

            }
        }

        return $filterList;
    }
    
}
