<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Model\Event;
use Magento\Catalog\Model\Product;

class AddToWishlistEvent extends Event
{
    /**
     * @var Product
     */
    protected $_product;

    /**
     * @return string
     */
    function getName()
    {
        return "Add to Wishlist";
    }

    /**
     * @return string
     */
    function getType()
    {
        return "add-to-wishlist-v1";
    }

    /**
     * @return array
     */
    function getDefaultProperties()
    {
        return [
            'productId' => null
        ];
    }

    /**
     * @return array
     */
    function generateProperties()
    {
        return [
            'productId' => $this->_product->getSku()
        ];
    }

    /**
     * @param Product $item
     */
    public function setProduct(Product $product)
    {
        $this->_product = $product;
    }
}