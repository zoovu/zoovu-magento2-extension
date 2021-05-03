<?php

namespace Semknox\Productsearch\Model\Filter;

class RangeFilter extends AbstractFilter
{

    public function getLabel()
    {
        $sxFilter = $this->_sxFilter;
        $label = false;

        if ($sxFilter->getType() == 'RANGE') {
            $label = $sxFilter->getActiveMin() . ' - ' . $sxFilter->getActiveMax();
            $label .= $sxFilter->getUnit() ? ' ' . $sxFilter->getUnit() : '';
        }

        return $label ?? '...';
    }

}
