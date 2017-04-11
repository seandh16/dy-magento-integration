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
     * @return mixed
     */
    function getName()
    {
        return "Purchase";
    }

    /**
     * @return mixed
     */
    function getType()
    {
        return "purchase-v1";
    }

    /**
     * @return mixed
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
     * @return mixed
     */
    function generateProperties()
    {
        $items = [];

        foreach ($this->_order->getAllVisibleItems() as $item) {
            /** @var \Magento\Sales\Model\Order\Item $item */
            $items[] = [
                'productId' => $item->getSku(),
                'quantity' => round($item->getQtyOrdered(), 2),
                'itemPrice' => $item->getPrice()
            ];
        }

        return [
            'value' => $this->_order->getGrandTotal(),
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