<?php

namespace DynamicYield\Integration\Model;

use DynamicYield\Integration\Helper\Data;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Quote\Model\Quote as Cart;
use Magento\Quote\Model\Quote\Item;

abstract class Event
{
    /**
     * @return string
     */
    abstract public function getName();

    /**
     * @return string
     */
    abstract public function getType();

    /**
     * @return array
     */
    abstract public function getDefaultProperties();

    /**
     * @return array
     */
    abstract public function generateProperties();

    /**
     * @return array
     */
    public function build()
    {
        $properties = array_replace($this->getDefaultProperties(), $this->generateProperties());
        $properties['dyType'] = $this->getType();
        $properties['uniqueRequestId'] =  $this->generateUniqueId();

        return [
            'name' => $this->getName(),
            'properties' => $properties
        ];
    }

    /**
     * Get all cart items
     * @param Cart $cart
     * @param Data|null $dataHelper
     * @param PriceHelper|null $priceHelper
     * @param array $except
     * @return array
     */
    public function getCartItems(Cart $cart, Data $dataHelper = null, PriceHelper $priceHelper = null, array $except = [])
    {
        $prepareItems = [];
        $items = [];
        $cartItems = $cart->getAllItems();

        if (!count($cartItems)) {
            return [];
        }

        /** @var Item $item */
        foreach ($cartItems as $item) {

            /**
             * Skip parent product types
             */
            if (in_array($item->getProductType(), [Type::TYPE_BUNDLE, Data::PRODUCT_GROUPED, Data::PRODUCT_CONFIGURABLE])) {
                continue;
            }

            $sku = $dataHelper ? $dataHelper->replaceSpaces($item->getSku()) : $item->getSku();

            if (in_array($item->getId(), $except) || isset($prepareItems[$sku])) {
                continue;
            }

            $product = $item->getProduct();

            if (!$product || !$dataHelper->validateSku($product)) {
                continue;
            }

            $prepareItems[$sku] = [
                'itemPrice' => round($priceHelper->currency($product->getFinalPrice(), false, false), 2),
                'productId' =>  $dataHelper->replaceSpaces($product->getSku()),
                'quantity' => round($item->getTotalQty(), 2)
            ];
        }

        foreach ($prepareItems as $item) {
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return string
     */
    public function generateUniqueId()
    {
        return (string) intval(str_pad(mt_rand(0, 999999999999), 10, '0', STR_PAD_LEFT));
    }
}
