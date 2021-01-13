<?php

namespace Semknox\Productsearch\Block\Theme\Html;


class Pager extends \Magento\Theme\Block\Html\Pager
{

    // check /vendor/magento/module-theme/view/frontend/templates/html/pager.phtml

    /**
     * Retrieve number of last page
     *
     * @return int
     */
    public function getLastPageNum()
    {
        if (!isset($this->getCollection()->_isSxSearch) || !$this->getCollection()->_isSxSearch) return parent::getLastPageNum();

        return $this->getCollection()->_sxLastPageNum;
    }


    /**
     * Retrieve last page URL
     *
     * @return string
     */
    public function getLastPageUrl()
    {
        if (!isset($this->getCollection()->_isSxSearch) || !$this->getCollection()->_isSxSearch) return parent::getLastPageUrl();

        return $this->getPageUrl($this->getLastPageNum());
    }

    /**
     * Return current page
     *
     * @return int
     */
    public function getCurrentPage()
    {
        if (is_object($this->_collection)) {
            if (!isset($this->_collection->_isSxSearch) || !$this->_collection->_isSxSearch) {
                return parent::getCurrentPage();
            }

            return $this->getCollection()->_sxCurrentPage;
        }

        return (int)$this->getRequest()->getParam($this->getPageVarName(), 1);
    }


    /**
     * Return page number of Next jump
     *
     * @return int|null
     */
    public function getNextJumpPage()
    {
        if (!isset($this->_collection->_isSxSearch)) return parent::getNextJumpPage();

        $frameEnd = $this->getFrameEnd();

        if ($this->getLastPageNum() - $frameEnd > 1) {
            return min($this->getLastPageNum() - 1, $frameEnd + $this->getJump());
        }

        return null;
    }

    /**
     * Retrieve next page URL
     *
     * @return string
     */
    public function getNextPageUrl()
    {
        if (!isset($this->_collection->_isSxSearch)) return parent::getNextPageUrl();

        return $this->getPageUrl($this->getCurrentPage() + 1);
    }


    /**
     * Return page number of Previous jump
     *
     * @return int|null
     */
    public function getPreviousJumpPage()
    {
        if (!isset($this->_collection->_isSxSearch)) return parent::getPreviousJumpPage();

        $frameStart = $this->getFrameStart();

        if ($frameStart - 1 > 1) {
            return max(2, $frameStart - $this->getJump());
        }

        return null;
    }

    /**
     * Retrieve previous page URL
     *
     * @return string
     */
    public function getPreviousPageUrl()
    {
        if (!isset($this->_collection->_isSxSearch)) return parent::getPreviousPageUrl();

        return $this->getPageUrl($this->getCurrentPage() - 1);
    }


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


    /**
     * Check if current page is a first page in collection
     *
     * @return bool
     */
    public function isFirstPage()
    {
        return (int) $this->getRequest()->getParam($this->getPageVarName(), 1) == 1;
    }
}
