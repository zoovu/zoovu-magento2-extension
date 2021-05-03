<?php

namespace Semknox\Productsearch\Model\Filter;

class BucketFilter extends AbstractFilter
{
    public function getLabel()
    {
        $sxFilter = $this->_sxFilter;
        $label = false;

        if ($sxFilter->getActiveOptions()) {

            $activeValues = [];
            foreach ($sxFilter->getActiveOptions() as $option) {
                
                // todo: improve core
                $activeValues[] = $option['key'];
            }
            $label = implode(', ', $activeValues);
        }

        return $label ?? '...';
    }
}
