<?php

namespace Semknox\Productsearch\Block\Theme\Html;


class Pager extends \Magento\Theme\Block\Html\Pager
{

    // check /vendor/magento/module-theme/view/frontend/templates/html/pager.phtml

    /**
     * Initialize frame data, such as frame start, frame start etc.
     *
     * @return $this
     */
    protected function _initFrame()
    {
        if (!$this->isFrameInitialized()) {
            $start = 0;
            $end = 0;

            $collection = $this->getCollection();
            if (!isset($collection->_isSxSearch)) return parent::_initFrame();

            if ($this->getLastPageNum() <= $this->getFrameLength()) {
                $start = 1;
                $end = $this->getLastPageNum();
            } else {
                $half = ceil($this->getFrameLength() / 2);
                if (
                    $this->getCurrentPage() >= $half &&
                    $this->getCurrentPage() <= $this->getLastPageNum() - $half
                ) {
                    $start = $this->getCurrentPage() - $half + 1;
                    $end = $start + $this->getFrameLength() - 1;
                } elseif ($this->getCurrentPage() < $half) {
                    $start = 1;
                    $end = $this->getFrameLength();
                } elseif ($this->getCurrentPage() > $this->getLastPageNum() - $half) {
                    $end = $this->getLastPageNum();
                    $start = $end - $this->getFrameLength() + 1;
                }
            }
            $this->_frameStart = $start;
            $this->_frameEnd = $end;

            $this->_setFrameInitialized(true);
        }

        return $this;
    }
}
