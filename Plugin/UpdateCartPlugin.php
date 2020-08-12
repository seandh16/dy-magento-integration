<?php

namespace DynamicYield\Integration\Plugin;

use Magento\Checkout\Model\Cart;
use Magento\Framework\Event\ManagerInterface;

class UpdateCartPlugin
{
    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * UpdateCartPlugin constructor.
     * @param Cart $cart
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        Cart $cart,
        ManagerInterface $eventManager
    ) {
        $this->cart = $cart;
        $this->_eventManager = $eventManager;
    }

    /**
     * @param Cart $subject
     * @param $data
     */
    public function beforeUpdateItems(\Magento\Checkout\Model\Cart $subject, $data)
    {
        $items = $this->cart->getItems();

        foreach ($items as $item) {
            foreach ($data as $updateId => $updateItem) {
                if ($item->getId() == $updateId) {
                    $qtyChange = $updateItem['qty'] - $item->getQty();

                    if ($qtyChange <= 0 || !$product = $item->getProduct()) {
                        continue;
                    }

                    $this->_eventManager->dispatch('dyi_add_item_to_cart', [
                        'product' => $product,
                        'qty' => $qtyChange,
                        'index' => $product->getId()
                    ]);
                }
            }
        }
    }
}
