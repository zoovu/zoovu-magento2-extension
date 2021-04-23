<?php

namespace Semknox\Productsearch\Block\CatalogSearch\SearchResult;

class ListProduct 
{
    /**
     * Retrieve additional blocks html
     *
     * @return string
     */
    public function afterGetAdditionalHtml(\Magento\CatalogSearch\Block\Result $parent, $result)
    {
        $collection = $parent->_getProductCollection();

        $contentBoxesCount = isset($collection->_sxContentResults) ? count($collection->_sxContentResults) : 0;
        if (!$contentBoxesCount) return $result;

        $productBoxesCount = count($collection) - $contentBoxesCount;
        $contentBoxEvery = ceil(($productBoxesCount / $contentBoxesCount) - 1);

        $html = '<script>document.addEventListener("DOMContentLoaded", function() {';

        $html .= "var magePriceBoxes = document.getElementsByClassName('price-box');";

        $html .= "var sxContent = []";

        foreach ($collection->_sxContentResults as $idx => $contentResult) {

            // to increase compatibility to older mage2 versions
            $html .= "
                for(var i = 0; i < magePriceBoxes.length; i++){
                    if(magePriceBoxes[i].getAttribute('data-price-box') == 'product-id-sxcontent-" . $idx . "'){
                        sxContent[" . $idx . "] = magePriceBoxes[i].parentNode.parentNode;
                        break;
                    }              
                };";

            $html .= "if(sxContent[" . $idx . "]){";

            // set Url
            $html .= "sxContent[" . $idx . "].getElementsByTagName('a')[0].href = '" . $contentResult->getLink() . "';";

            // remove product actions
            $html .= "sxContent[" . $idx . "].getElementsByClassName('product-item-actions')[0].remove();";

            // remove price container
            $html .= "document.getElementById('product-price-sxcontent-" . $idx . "').remove();";

            // set image
            $html .= "sxContent[" . $idx . "].getElementsByClassName('product-image-photo')[0].src = '" . $contentResult->getImage() . "';";

            $html .= "}";
        }

        // move boxes
        $html .= "var mageProductList = document.getElementsByClassName('product-item');";
        $html .= "
                var contentBoxCounter = 0;
                var contentResultIdx = 0;
                for (var i = $contentBoxesCount; i < mageProductList.length; i++) {

                    if(contentBoxCounter == " . $contentBoxEvery . " && sxContent[contentResultIdx]){
                        contentBox = sxContent[contentResultIdx];
                        mageProductList[i].parentNode.insertBefore(contentBox.parentNode, mageProductList[i]);
                        contentBoxCounter = 0;
                        i--;
                        contentResultIdx++;
                    } else {
                        contentBoxCounter++;
                    }

                }";

        $html .= '});</script>';

        return $html . $result;
    }
  

}
