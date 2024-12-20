<?php

namespace Semknox\Productsearch\Model;

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

        $sxGroupIdentifier = ($this->_product->getData('sxGroupIdentifier') && strlen($this->_product->getData('sxGroupIdentifier')) > 0) ? $this->_product->getData('sxGroupIdentifier') : $this->_product->getId();
        $sxArticle['groupIdentifier'] = (string) $sxGroupIdentifier;

        $sxArticle['name'] = (string) $this->_product->getName();

        $sxArticle['productUrl'] = $this->_product->getData('sxProductUrl') ?? $this->_product->getUrlModel()->getProductUrl($this->_product, ['_escape' => true]);
        //$sxArticle['productUrl'] = explode('?', $sxArticle['productUrl']);
        //$sxArticle['productUrl'] = (string) $sxArticle['productUrl'][0];

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

        if(strlen($sxArticle['name']) == 0){

            if(isset($sxArticle['attributes']['name']) && strlen(isset($sxArticle['attributes']['name']))){
                $sxArticle['name'] = $sxArticle['attributes']['name'];

            } elseif($this->_product->getData('sxParentName') && strlen($this->_product->getData('sxParentName')) > 0){
                $sxArticle['name'] = $sxArticle['attributes']['name'] = $this->_product->getData('sxParentName');

            } elseif(isset($sxArticle['attributes']['sku']) && strlen(isset($sxArticle['attributes']['sku']))){
                $sxArticle['name'] = $sxArticle['attributes']['sku'];

            } else {
                $sxArticle['name'] = $sxArticle['identifier'];
            }
        }

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
            //'visibility' // added 16.11.2020 + removed 29.10.2024
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

        // get WYOMIND prices, if exist
        $attributesWithPrices = $this->_getPrices($attributes, $transformerArgs, 'catalog_product_index_price_store');

        // get "normal" prices
        if(count($attributes) == count($attributesWithPrices)){
            $attributes = $this->_getPrices($attributes, $transformerArgs);
        } else {
            $attributes = $attributesWithPrices;
        }
        
        return array_values($attributes);
    }

    protected function _getPrices($attributes, $transformerArgs = array(), $tableName = 'catalog_product_index_price')
    {
        $resourceConnection = $transformerArgs['resourceConnection'];

        $connection = $resourceConnection->getConnection();

        $tableName = $resourceConnection->getTableName($tableName);

        if(!$connection->isTableExists($tableName)) return $attributes;

        $select = $connection->select()
            ->from($tableName)
            ->where('entity_id = ?', $this->_product->getId());

        if($transformerArgs['websiteId']) {    
            $select->where('website_id = ?', $transformerArgs['websiteId']);
        }

        if($tableName == 'catalog_product_index_price_store' && $transformerArgs['storeId']){
            $select->where('store_id = ?', $transformerArgs['storeId']);
        }

        foreach($connection->fetchAll($select) as $row){

            $keyArray = [];
            $customerGroupId = 0;
            foreach($row as $columnName => $columnValue){

                if($columnName == 'customer_group_id'){
                    $customerGroupId = $columnValue;
                }

                if (\stripos($columnName, "price") !== false || in_array($columnName, ['entity_id','tier_price'])) continue;

                $keyArray[] = implode('', array_map("ucfirst", explode('_',$columnName))).": ".$columnValue;
            }

            $key = "(".implode(", ", $keyArray).")";

            foreach($row as $columnName => $columnValue){

                if(\stripos($columnName, "price") === false || $columnName == 'tier_price') continue;

                $attribute = [
                    'key' => $columnName, //\sprintf("%s %s", $columnName, $key),
                    'value' => \sprintf("%s %s", $columnValue, $transformerArgs['currency'])
                ];

                if($customerGroupId && strlen("$customerGroupId") > 0){
                    $attribute["userGroups"][] = "$customerGroupId";
                }

                $attributes[] = $attribute;
            }
           
        }

        return $attributes;
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
