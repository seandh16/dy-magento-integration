<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Model\Event;
use Magento\Catalog\Model\Product;

class AddToWishlistEvent extends Event
{
    /**
     * @var Product
     */
    protected $_productSku;

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
            'productId' => $this->_productSku
        ];
    }

    /**
     * @param string $productSku
     */
    public function setProductSku($productSku)
    {
        $this->_productSku = $productSku;
    }
}