<?php

namespace Semknox\Productsearch\Model\Filter;

class TreeFilter extends AbstractFilter
{

    protected function _initItems()
    {
        $data = $this->_getItemsData();
        $items = [];


        // get level
        $level = [];
        foreach ($this->_sxFilter->getOptions() as $sxOption) {
            $level[$sxOption->getValue()] = count(explode('/', $sxOption->getValue()));
        }
        $zeroLevel = min($level);

        foreach ($this->_sxFilter->getOptions() as $sxOption) {
            $label = str_repeat(' ',$level[$sxOption->getValue()] - $zeroLevel) . $sxOption->getName();
            $items[] = $this->_createItem($label, $sxOption->getValue(), $sxOption->getNumberOfResults());
        }

        $this->_items = $items;
        return $this;
    }
    
}
