<?php

namespace Semknox\Productsearch\Application\Model;

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
        $sxArticle['name'] = 'Transformer Test';

        return $sxArticle;

    }

}
