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

        return $sxArticle;

    }

    /**
     * get categories of product
     * 
     * @return array sssss
     * @throws DatabaseConnectionException 
     * @throws DatabaseErrorException 
     * @throws InvalidArgumentException 
     */
    protected function _getCategories($transformerArgs)
    {
        $categoryIds = $this->_product->getCategoryIds();
        $storeId = $transformerArgs['sxConfig']->get('shopId');
        $storeRootCategory = $transformerArgs['sxConfig']->get('storeRootCategory');

        if(empty($categoryIds)) return $categoryIds;

        $categoryCollection = $transformerArgs['categoryCollectionFactory']->create();
        $categoryCollection->addFieldToFilter('entity_id', $categoryIds);
        $categoryCollection->addAttributeToFilter('is_active', '1');

        $categories = [];
        foreach ($categoryCollection->getItems() as $category) {

            $pathCategorieIds = explode('/',$category->getPath());

            $pathCategoryCollection = $transformerArgs['categoryCollectionFactory']->create();
            $pathCategoryCollection->addFieldToFilter('entity_id', $pathCategorieIds);
            $pathCategoryCollection->setStore($storeId);
            $pathCategoryCollection->addAttributeToSelect('name');
            $pathCategoryCollection->addAttributeToFilter('is_active','1');

            $categoryPath = [];
            foreach($pathCategoryCollection->getItems() as $pathCategory){

                if($storeRootCategory == $pathCategory->getId()) continue;

                $categoryPath[] = $pathCategory->getName() ? $pathCategory->getName() : $pathCategory->getId();
            }

            if(empty($categoryPath)) continue;

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
        $mageImages= $this->_product->getMediaGalleryEntries();

        $images = array();
        foreach($mageImages as $image){

            $images[] = [
                'url' => $image->getUrl(),
                'type' => $image->getTypes()
            ];

        }

        /*
        $imageSuffix = '';
        $imageTypeSuffix = array();
        if (isset($transformerArgs['imageUrlSuffix'])) {
            if (!is_array($transformerArgs['imageUrlSuffix'])) {
                $imageSuffix = (string) $transformerArgs['imageUrlSuffix'];
            } else {
                $imageTypeSuffix = $transformerArgs['imageUrlSuffix'];
            }
        }

        if (isset($sxImages['Pics'])) {

            foreach ($sxImages['Pics'] as $image) {
                $images[] = [
                    'url' => $image,
                    'type' => 'SMALL'
                ];
            }
        }

        if (isset($sxImages['ZoomPics'])) {

            foreach ($sxImages['ZoomPics'] as $image) {
                $images[] = [
                    'url' => $image['file'],
                    'type' => 'LARGE'
                ];
            }
        }

        if (isset($sxImages['Icons'])) {

            foreach ($sxImages['Icons'] as $image) {
                $images[] = [
                    'url' => $image,
                    'type' => 'THUMB'
                ];
            }
        }

        //check for correct file type
        foreach ($images as $key => $image) {

            $fileExtension = strtolower(pathinfo($image['url'], PATHINFO_EXTENSION));

            if (!in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
                unset($images[$key]);
                continue;
            }

            if (isset($imageTypeSuffix[$image['type']])) {
                $images[$key]['url'] .= (string) $imageTypeSuffix[$image['type']];
            } else {
                $images[$key]['url'] .= $imageSuffix;
            }
        }
        */

        return array_values($images);
    }

}
