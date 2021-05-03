<?php


namespace Semknox\Productsearch\Block\Amasty\Shopby\Navigation;

use Magento\LayeredNavigation\Block\Navigation\FilterRenderer as ParentFilterRenderer;
use Semknox\Productsearch\Interceptor\Block\LayeredNavigation\Navigation\FilterRenderer as InterceptorFilterRenderer;

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
            if (!$this->_isSxSearch) {
                return parent::render($filter);
            }

            $semknoxParent = new ParentFilterRenderer(
                $this->_context,
                $this->_data
            );

            if (!isset($filter->_sxFilter) || $filter->_sxFilter->getType() !== 'RANGE') {

                return $semknoxParent::render($filter);
            } else {

                $sxFilter = $filter->_sxFilter;
                $icFilterRenderer = new InterceptorFilterRenderer($this->_sxHelper);

                return $icFilterRenderer->afterRender($semknoxParent, '', $filter);

                /*
                // todo: move to template file
                return "<div class='slider-wrapper'>
                                <div class='slider sxRangeFilter' id='sx_" . $filter->getName() . "' 
                                    data-start='" . $sxFilter->getActiveMin() . "'
                                    data-end='" . $sxFilter->getActiveMax() . "'
                                    data-range-min='" . $sxFilter->getMin() . "'
                                    data-range-max='" . $sxFilter->getMax() . "'
                                    data-url='" . $filter->getRemoveUrl() . "'
                                ></div>
                                <div class='slider-helper'>
                                    <input class='start' value='' min='' type='number' name='num1'>
                                    <span>-</span>
                                    <input class='end' value='' max='' type='number' name='num2'>
                                    <span class='unit'>" . $sxFilter->getUnit() . "</span>
                                    <button class='' type='button'><i class='fa fa-angle-right'></i></button>
                                </div>
                        </div>";
                        */
            }
        }

        protected function getTemplateByFilterSetting($filterSetting)
        {
            $template = parent::getTemplateByFilterSetting($filterSetting);

            if (!$this->_isSxSearch) {
                return 'Amasty_Shopby::' . $template;
            }

            return $template;
        }

        protected function getCustomTemplateForCategoryFilter($filterSetting)
        {
            $template = parent::getCustomTemplateForCategoryFilter($filterSetting);

            if (!$this->_isSxSearch) {
                return 'Amasty_Shopby::' . $template;
            }

            return $template;
        }

        public function checkedFilter($arg)
        {
            return parent::checkedFilter($arg);
        }
    }
} else {
    class FilterRenderer extends ParentFilterRenderer
    {
    }
}
