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
    public function getName()
    {
        return "Add to Wishlist";
    }

    /**
     * @return string
     */
    public function getType()
    {
        return "add-to-wishlist-v1";
    }

    /**
     * @return array
     */
    public function getDefaultProperties()
    {
        return [
            'productId' => null
        ];
    }

    /**
     * @return array
     */
    public function generateProperties()
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
