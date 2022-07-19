<?php

namespace DynamicYield\Integration\Model\Event;

use DynamicYield\Integration\Helper\Data;
use DynamicYield\Integration\Model\Event;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Sales\Model\Order;

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
     * @var Data
     */
    protected $_dataHelper;

    /**
     * PurchaseEvent constructor
     *
     * @param Order $order
     * @param ProductRepository $productRepository
     * @param Data $data
     * @param PriceHelper $priceHelper
     */
    public function __construct(
        Order $order,
        ProductRepository $productRepository,
        Data $data,
        PriceHelper $priceHelper
    ) {
        $this->_order = $order;
        $this->_productRepository = $productRepository;
        $this->_dataHelper = $data;
        $this->_priceHelper = $priceHelper;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "Purchase";
    }

    /**
     * @return string
     */
    public function getType()
    {
        return "purchase-v1";
    }

    /**
     * @return array
     */
    public function getDefaultProperties()
    {
        return [
            'uniqueTransactionId' => null,
            'value' => null,
            'currency' => null,
            'cart' => []
        ];
    }

    /**
     * @return array
     */
    public function generateProperties()
    {
        $items = [];

        foreach ($this->_order->getAllItems() as $item) {

            /**
             * Skip parent product types
             */
            if (in_array($item->getProductType(), [Type::TYPE_BUNDLE, Data::PRODUCT_GROUPED, Data::PRODUCT_CONFIGURABLE])) {
                continue;
            }

            $product = $item->getProduct();

            if (!$product) {
                continue;
            }

            $sku = $this->_dataHelper->validateSku($product) ? $product->getSku() : $product->getData('sku');

            $items[] = [
                'productId' => $this->_dataHelper->replaceSpaces($sku),
                'quantity' => round($item->getQtyOrdered(), 2),
                'itemPrice' => round($this->_priceHelper->currency($product->getFinalPrice(), false, false), 2)
            ];
        }

        return [
            'uniqueTransactionId' => (string) $this->_order->getIncrementId(),
            'value' => round($this->_order->getGrandTotal(), 2),
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
