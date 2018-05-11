<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Model\Event;
use Magento\Sales\Model\Order;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;


class PurchaseEvent extends Event
{
    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var ProductRepository
     */
    protected $_productRepository;

    /**
     * @var PriceHelper
     */
    protected $_priceHelper;

    /**
     * PurchaseEvent constructor
     *
     * @param Order $order
     * @param ProductRepository $productRepository
     * @param PriceHelper $priceHelper
     */
    public function __construct(
        Order $order,
        ProductRepository $productRepository,
        PriceHelper $priceHelper
    )
    {
        $this->_order = $order;
        $this->_productRepository = $productRepository;
        $this->_priceHelper = $priceHelper;
    }

    /**
     * @return string
     */
    function getName()
    {
        return "Purchase";
    }

    /**
     * @return string
     */
    function getType()
    {
        return "purchase-v1";
    }

    /**
     * @return array
     */
    function getDefaultProperties()
    {
        return [
            'value' => null,
            'currency' => null,
            'cart' => []
        ];
    }

    /**
     * @return array
     */
    function generateProperties()
    {
        $items = [];

        foreach ($this->_order->getAllItems() as $item) {

            /**
             * Skip bundle and grouped products (out of scope)
             */
            if($item->getProductType() == Type::TYPE_BUNDLE || $item->getProductType() == Data::PRODUCT_GROUPED) {
                continue;
            }

            $product = $item->getProduct();


            if(!$product) {
                continue;
            }

            $items[] = [
                'productId' => $product->getSku(),
                'quantity' => round($item->getQtyOrdered(), 2),
                'itemPrice' => round($this->_priceHelper->currency($product->getData('price'),false,false),2)
            ];
        }

        return [
            'value' => round($this->_order->getGrandTotal(),2),
            'currency' => $this->_order->getOrderCurrencyCode(),
            'cart' => $items
        ];
    }

    /**
     * @param $orderId
     * @return $this
     */
    public function setOrder($orderId)
    {
        $this->_order->loadByAttribute('entity_id', $orderId);

        return $this;
    }
}