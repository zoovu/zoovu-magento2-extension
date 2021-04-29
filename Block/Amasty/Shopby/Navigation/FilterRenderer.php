<?php

namespace Semknox\Productsearch\Block\Amasty\Shopby\Navigation;

use Semknox\Productsearch\Block\LayeredNavigation\Navigation\FilterRenderer as ParentFilterRenderer;

if (class_exists('\Amasty\Shopby\Block\Navigation\FilterRenderer')) {

    class FilterRenderer extends \Amasty\Shopby\Block\Navigation\FilterRenderer
    {

        public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Amasty\Shopby\Helper\FilterSetting $settingHelper,
            \Amasty\Shopby\Helper\UrlBuilder $urlBuilder,
            \Amasty\Shopby\Helper\Data $helper,
            \Amasty\Shopby\Helper\Category $categoryHelper,
            \Magento\Catalog\Model\Layer\Resolver $resolver,
            \Amasty\ShopbyBase\Helper\Data $baseHelper,
            \Semknox\Productsearch\Helper\SxHelper $sxHelper,
            \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
            array $data = []
        ) {

            $this->_sxHelper = $sxHelper;
            $this->_filterItem = $filterItemFactory;
            $this->_context = $context;
            $this->_data = $data;

            $this->_isSxSearch = $sxHelper->isSearch() && $sxHelper->isSxSearchFrontendActive();

            $this->_sxFilters = $this->_sxHelper->getSxResponseStore('filterList', []);

            parent::__construct(
                $context,
                $settingHelper,
                $urlBuilder,
                $helper,
                $categoryHelper,
                $resolver,
                $baseHelper,
                $data
            );
        }

        public function render($filter)
        {
            if(!$this->_isSxSearch){
                return parent::render($filter);
            }

            $semknoxParent = new ParentFilterRenderer(
                $this->_sxHelper,
                $this->_filterItem,
                $this->_context ,
                $this->_data
            );

            return $semknoxParent->render($filter);

        }

        protected function getTemplateByFilterSetting($filterSetting)
        {
            $template = parent::getTemplateByFilterSetting($filterSetting);

            if(!$this->_isSxSearch){
                return 'Amasty_Shopby::'.$template;
            }

            return $template;
        }

        protected function getCustomTemplateForCategoryFilter($filterSetting)
        {   
            $template = parent::getCustomTemplateForCategoryFilter($filterSetting);

            if(!$this->_isSxSearch){
                return 'Amasty_Shopby::'.$template;
            }

            return $template;
        }
    }

} else {
    class FilterRenderer extends ParentFilterRenderer
    {
    }
}