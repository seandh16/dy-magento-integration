<?php

namespace DynamicYield\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;

class AddToWishlistObserver extends AbstractObserver
{
    const EVENT_TYPE = 'wishlist_add_product';

    /**
     * @param Observer $observer
     * @return mixed
     */
    function dispatch(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        $productSku = $product->getSku();
        $item = $observer->getEvent()->getItem();
        if($item) {
            if($simple = $item->getOptionByCode('simple_product')) {
                if($simpleId = $simple->getProductId()) {
                    $productSku = $this->_productResource->getAttributeRawValue($simpleId, 'sku', 0)['sku'];
                }
            }
        }


        $this->_addToWishlistEvent->setProductSku($productSku);
        $data = $this->_addToWishlistEvent->build();

        return $this->buildResponse([
            'type' => self::EVENT_TYPE,
            'properties' => $data
        ]);
    }
}