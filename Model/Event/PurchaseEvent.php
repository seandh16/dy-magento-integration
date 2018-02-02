<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Model\Event;
use Magento\Sales\Model\Order;

class PurchaseEvent extends Event
{
    /**
     * @var Order
     */
    protected $_order;

    /**
     * PurchaseEvent constructor
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->_order = $order;
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

        foreach ($this->_order->getAllVisibleItems() as $item) {
            /** @var \Magento\Sales\Model\Order\Item $item */
            $items[] = [
                'productId' => $item->getProduct()->getData('sku'),
                'quantity' => round($item->getQtyOrdered(), 2),
                'itemPrice' => round($item->getPrice(),2)
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