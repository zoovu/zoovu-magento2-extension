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
    public function __construct($mageProduct)
    {
        $this->_product = $mageProduct;
    }


    /**
     * transform oxid article to semknox-product
     */
    public function transform($transformerArgs = array())
    {
        $sxArticle = array();

        $sxArticle['identifier'] = $this->_product->getId();
        $sxArticle['groupIdentifier'] = $this->_product->getParentId() ? $this->_product->getParentId() : $this->_product->getId();

        $sxArticle['name'] = $this->_product->getName();

        $sxArticle['productUrl'] = $this->_product->getUrlModel()->getProductUrl($this->_product, ['_escape' => true]);
        $sxArticle['productUrl'] = explode('?', $sxArticle['productUrl']);
        $sxArticle['productUrl'] = $sxArticle['productUrl'][0];

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
    protected function _getImages($transformerArgs = array())
    {
        $mageImages = $this->_product->getMediaGalleryEntries();
        $images = array();
        foreach ($mageImages as $image) {

            if ((bool) $image->isDisabled() || $image->getMediaType() != 'image') continue;

            foreach ($image->getTypes() as $type) {

                if (stripos($type, 'video') !== false) continue;

                switch ($type) {
                    case 'small_image':
                        $type = 'SMALL';
                        break;
                    case 'thumbnail':
                        $type = 'THUMB';
                        break;
                    case 'swatch_image':
                        $type = 'THUMB';
                        break;
                    default:
                        $type = 'LARGE';
                        break;
                }

                $images[] = [
                    'url' => trim($transformerArgs['mediaUrl'], '/') . $image->getFile(),
                    'type' => $type
                ];
            }
        }

        return $images;
    }


    /**
     * get attributes of product
     */
    protected function _getAttributes($transformerArgs = array())
    {
        $attribtesToExclude = ['image', 'thumbnail', 'media', 'gallery', 'category'];

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

            $attributes = $this->_transformAttribute($code, $value, $transformerArgs, $attributes);
        }

        return array_values($attributes);
    }


    protected function _transformAttribute($code, $value, $transformerArgs, $attributes)
    {
        $productModel = $transformerArgs['productModel'];

        if (is_array($value) || is_object($value)) {

            foreach ($value as $c => $v) {
                $attributes = $this->_transformAttribute($c, $v, $transformerArgs, $attributes);
            }
            return $attributes;
        }

        $key = $productModel->getAttribute($code) ? $productModel->getAttribute($code)->getStoreLabel() : $code;
        if (!$key) $key = $code;

        if ($code == 'price') $value .= ' ' . $transformerArgs['currency'];

        $attributes[$code] = [
            'key' => $key,
            'value' => (string) $value
        ];

        return $attributes;
    }
}
