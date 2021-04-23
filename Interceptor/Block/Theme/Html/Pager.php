<?php

namespace Semknox\Productsearch\Interceptor\Block\Theme\Html;

class Pager
{

    // check /vendor/magento/module-theme/view/frontend/templates/html/pager.phtml

    private function _isNotSxSearch(\Magento\Theme\Block\Html\Pager $parent){
        return !isset($parent->getCollection()->_isSxSearch) || !$parent->getCollection()->_isSxSearch;
    }


    /**
     * Retrieve number of last page
     *
     * @return int
     */
    public function afterGetLastPageNum(\Magento\Theme\Block\Html\Pager $parent, $result)
    {
        if($this->_isNotSxSearch($parent)) return $result;

        return $parent->getCollection()->_sxLastPageNum;
    }


    /**
     * Retrieve last page URL
     *
     * @return string
     */
    public function afterGetLastPageUrl(\Magento\Theme\Block\Html\Pager $parent, $result)
    {
        if($this->_isNotSxSearch($parent)) return $result;

        return $parent->getPageUrl($parent->getLastPageNum());
    }

    /**
     * Return current page
     *
     * @return int
     */
    public function afterGetCurrentPage(\Magento\Theme\Block\Html\Pager $parent, $result)
    {
        if (is_object($parent->getCollection())) {

            if ($this->_isNotSxSearch($parent)){
                return $result;
            }

            return $parent->getCollection()->_sxCurrentPage;
        }

        return (int) $parent->getRequest()->getParam($parent->getPageVarName(), 1);
    }


    /**
     * Return page number of Next jump
     *
     * @return int|null
     */
    public function afterGetNextJumpPage(\Magento\Theme\Block\Html\Pager $parent, $result)
    {
        if ($this->_isNotSxSearch($parent)) return $result;

        $frameEnd = $parent->getFrameEnd();

        if ($parent->getLastPageNum() - $frameEnd > 1) {
            return min($parent->getLastPageNum() - 1, $frameEnd + $parent->getJump());
        }

        return null;
    }

    /**
     * Retrieve next page URL
     *
     * @return string
     */
    public function afterGetNextPageUrl(\Magento\Theme\Block\Html\Pager $parent, $result)
    {
        if ($this->_isNotSxSearch($parent)) return $result;

        return $parent->getPageUrl($parent->getCurrentPage() + 1);
    }


    /**
     * Return page number of Previous jump
     *
     * @return int|null
     */
    public function afterGetPreviousJumpPage(\Magento\Theme\Block\Html\Pager $parent, $result)
    {
        if ($this->_isNotSxSearch($parent)) return $result;

        $frameStart = $parent->getFrameStart();

        if ($frameStart - 1 > 1) {
            return max(2, $frameStart - $parent->getJump());
        }

        return null;
    }

    /**
     * Retrieve previous page URL
     *
     * @return string
     */
    public function afterGetPreviousPageUrl(\Magento\Theme\Block\Html\Pager $parent, $result)
    {
        if ($this->_isNotSxSearch($parent)) return $result;

        return $parent->getPageUrl($parent->getCurrentPage() - 1);
    }

    /**
     * Check if current page is a first page in collection
     *
     * @return bool
     */
    public function afterIsFirstPage(\Magento\Theme\Block\Html\Pager $parent, $result)
    {
        if ($this->_isNotSxSearch($parent)) return $result;

        return (int) $parent->getRequest()->getParam($parent->getPageVarName(), 1) == 1;
    }

    /**
     * Check if current page is a last page in collection
     *
     * @return bool
     */
    public function afterIsLastPage(\Magento\Theme\Block\Html\Pager $parent, $result)
    {
        if ($this->_isNotSxSearch($parent)) return $result;

        return (int) $parent->getRequest()->getParam($parent->getPageVarName(), 1) == $parent->getLastPageNum();
    }
}
