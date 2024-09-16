<?php

namespace Semknox\Productsearch\Model;

use InvalidArgumentException;
use Semknox\Core\Transformer\AbstractProductTransformer;

class ArticleTransformer extends AbstractProductTransformer
{

    protected $_product;

    /**
     * Class constructor.
     */
    public function __construct(
        $mageProduct
        )
    {
        $this->_product = $mageProduct;
    }


    /**
     * transform oxid article to semknox-product
     */
    public function transform($transformerArgs = array())
    {
        $sxArticle = array();

        $sxArticle['identifier'] = (string) $this->_product->getId();
        $sxArticle['groupIdentifier'] = (isset($this->_product->sxGroupIdenifier) && $this->_product->sxGroupIdenifier) ? $this->_product->sxGroupIdenifier : $this->_product->getId();
        $sxArticle['groupIdentifier'] = (string) $sxArticle['groupIdentifier'];

        $sxArticle['name'] = (string) $this->_product->getName();

        $sxArticle['productUrl'] = $this->_product->getUrlModel()->getProductUrl($this->_product, ['_escape' => true]);
        $sxArticle['productUrl'] = explode('?', $sxArticle['productUrl']);
        $sxArticle['productUrl'] = (string) $sxArticle['productUrl'][0];

        $categories = array();
        if (!isset($transformerArgs['disableCategories']) || !$transformerArgs['disableCategories']) {
            $categories = $this->_getCategories($transformerArgs);
        }

        if (!count($categories)) {
            $categories = [
                [
                    'path' => ['uncategorized']
                ]
            ];
        }

        $sxArticle['categories'] = $categories;

        $sxArticle['images'] = $this->_getImages($transformerArgs);

        $sxArticle['attributes'] = $this->_getAttributes($transformerArgs);

        $sxArticle['name'] = (strlen($sxArticle['name']) == 0 && isset($sxArticle['attributes']['name'])) ? $sxArticle['attributes']['name']['value'] : '';
        $sxArticle['name'] = (strlen($sxArticle['name']) == 0 && isset($sxArticle['attributes']['sku'])) ? $sxArticle['attributes']['sku']['value'] : '';
        $sxArticle['name'] = (strlen($sxArticle['name']) == 0) ?  $sxArticle['identifier'] : '';

        return $sxArticle;
    }

    /**
     * get categories of product
     * 
     * @return array $categories
     */
    protected function _getCategories($transformerArgs)
    {
        $categoryIds = $this->_product->getCategoryIds();
        $storeId = $transformerArgs['sxConfig']->get('shopId');
        $storeRootCategory = $transformerArgs['sxConfig']->get('storeRootCategory');

        if (empty($categoryIds)) return $categoryIds;

        $categoryCollection = $transformerArgs['categoryCollectionFactory']->create();
        $categoryCollection->addFieldToFilter('entity_id', $categoryIds);
        $categoryCollection->addAttributeToFilter('is_active', '1');

        $categories = [];
        foreach ($categoryCollection->getItems() as $category) {

            $pathCategorieIds = explode('/', $category->getPath());

            $pathCategoryCollection = $transformerArgs['categoryCollectionFactory']->create();
            $pathCategoryCollection->addFieldToFilter('entity_id', $pathCategorieIds);
            $pathCategoryCollection->setStore($storeId);
            $pathCategoryCollection->addAttributeToSelect('name');
            $pathCategoryCollection->addAttributeToFilter('is_active', '1');

            $categoryPath = [];
            foreach ($pathCategoryCollection->getItems() as $pathCategory) {

                if ($storeRootCategory == $pathCategory->getId()) continue;

                $categoryPath[] = $pathCategory->getName() ? $pathCategory->getName() : $pathCategory->getId();
            }

            if (empty($categoryPath)) continue;

            $categories[] = [
                'path' => $categoryPath
            ];
        }

        return $categories;
    }

    /**
     * get images of product
     */
    protected function _getImages($transformerArgs = [])
    {
        $mageImages = $this->_product->getMediaGalleryImages();
        $removeFromImageUrl = $transformerArgs['sxConfig']->get('removeFromImageUrl');
        $images = [];

        $imageTypes = [];
        $imageTypeKeys = [
            'small_image' => 'SMALL',
            'thumbnail' => 'THUMB',
            'swatch_image' => 'THUMB'
        ];
        foreach($imageTypeKeys as $key => $sxType){
            if($image = $this->_product->getData($key)){
                $imageTypes[$image][] = $sxType;
            }
        }

        foreach ($mageImages as $image) {
            if(isset($imageTypes[$image->getFile()])){
                foreach($imageTypes[$image->getFile()] as $type){
                    $images[$image->getFile().'_'. $type] = [
                        'url' => str_replace($removeFromImageUrl,'',$image->getUrl()),
                        'type' => $type,
                    ];
                }
            }

            $images = array_values($images);
            
            $images[] = [
                'url' => str_replace($removeFromImageUrl, '', $image->getUrl()),
                'type' => 'LARGE',
            ];
        }

        // placeholder if no images set
        if(!count($images)){
            $imageHelper = $transformerArgs['imageHelper'];
            $assetsRepos = $transformerArgs['assetsRepos'];
            $appEmulation = $transformerArgs['appEmulation'];

            $storeId = $transformerArgs['sxConfig']->get('shopId');
            $appEmulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);

            $imagePlaceholder = $imageHelper->create();
            $images[] = [
                'url' => str_replace($removeFromImageUrl, '', $assetsRepos->getUrl($imagePlaceholder->getPlaceholder('image'))),
                'type' => 'LARGE',
            ];
            $appEmulation->stopEnvironmentEmulation();
        }

        return $images;
    }


    /**
     * get attributes of product
     */
    protected function _getAttributes($transformerArgs = array())
    {
        $attribtesToExclude = [
            'image', 'thumbnail', 'media', 'gallery', 'category', // already definded
            'attribute_set_id', 'has_options', 'required_options', 'request_path', 'tier_price_changed',
            'status','url_key','options_container','store_id','msrp_display_actual_price_type', 'gift_message_available',
            'visibility' // added 16.11.2020
        ];

        $attributes = [];

        foreach ($this->_product->getData() as $code => $value) {

            $doNotAdd = false;
            foreach ($attribtesToExclude as $needle) {
                if (stripos($code, $needle) !== false) {
                    $doNotAdd = true;
                    continue;
                }
            }

            if ($doNotAdd) continue;

            $appEmulation = $transformerArgs['appEmulation'];
            $storeId = $transformerArgs['sxConfig']->get('shopId');
            $appEmulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);
            $attributes = $this->_transformAttribute($code, $value, $transformerArgs, $attributes);
            $appEmulation->stopEnvironmentEmulation();
        }

        return array_values($attributes);
    }


    protected function _transformAttribute($code, $value, $transformerArgs, $attributes)
    {
        $productModel = $transformerArgs['productResourceModel'];
        $storeId  = $transformerArgs['sxConfig']->get('shopId');

        if ((is_array($value) && !empty($value)) || is_object($value)) {

            foreach ($value as $c => $v) {
                $attributes = $this->_transformAttribute($c, $v, $transformerArgs, $attributes);
            }
            return $attributes;
        }

        $key = $code;
        if($productModel->getAttribute($code) && $productModel->getAttribute($code)->setStoreId($storeId)){
            $attributeModel = $productModel->getAttribute($code)->setStoreId($storeId);
            $value = $attributeModel->getFrontend()->getValue($this->_product);
            $key = $attributeModel->getStoreLabel() ? $attributeModel->getStoreLabel() : $key;
        }

        if(empty($value)) return $attributes;

        if(!is_array($value) && stripos($code,'price') !== false && strlen($value)>5 ) $value .= ' ' . $transformerArgs['currency'];

        if (is_array($value) || is_object($value)) return $attributes;

        if(in_array($code, ['name', 'sku'])){
            $attributes[$code] = [
                'key' => (string) $code,
                'value' => (string) $value
            ];
        }

        $attributes[] = [
            'key' => (string) $key,
            'value' => (string) $value
        ];

        //$transformerArgs['sxHelper']->log("$code: $key: $value");

        return $attributes;
    }
}
