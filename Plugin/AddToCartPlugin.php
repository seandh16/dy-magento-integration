<?php

namespace DynamicYield\Integration\Plugin;

use Closure;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote as Cart;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class AddToCartPlugin
{
    /**
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var ProductRepository
     */
    protected $_productRepository;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * AddToCartPlugin constructor
     *
     * @param ManagerInterface $eventManager
     * @param ProductRepository $productRepository
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ManagerInterface $eventManager,
        ProductRepository $productRepository,
        RequestInterface $request,
        StoreManagerInterface $storeManager
    ) {
        $this->_eventManager = $eventManager;
        $this->_productRepository = $productRepository;
        $this->_request = $request;
        $this->_storeManager = $storeManager;
    }

    /**
     * @param Cart $cart
     * @param Closure $proceed
     * @param Product $productInfo
     * @param array|DataObject $requestInfo
     * @return mixed
     */
    public function aroundAddProduct(Cart $cart, Closure $proceed, Product $productInfo, $requestInfo)
    {
        $product = $this->_initProduct($productInfo);
        $request = $this->_initProductRequest($requestInfo);
        $closure = $proceed($product, $request);

        if ($closure) {
            $qty = $request->getData('qty') ? $request->getData('qty') : 1;

            $this->_eventManager->dispatch('dyi_add_item_to_cart', [
                'product' => $product,
                'qty' => $qty
            ]);
        }

        return $cart;
    }

    /**
     * @param $product
     * @return bool|ProductInterface|mixed
     */
    protected function _initProduct($product)
    {
        if ($product instanceof Product) {
            if (!$product->getId()) {
                return false;
            }

            return $product;
        }

        if (is_numeric($product) || is_string($product)) {
            try {
                /** @var Store $store */
                $store = $this->_storeManager->getStore();
                return $this->_productRepository->getById($product, false, $store->getId());
            } catch (NoSuchEntityException $exception) {
            }
        }

        return false;
    }

    /**
     * @param $productRequest
     * @return DataObject
     */
    protected function _initProductRequest($productRequest)
    {
        if (is_array($productRequest)) {
            return new DataObject($productRequest);
        } elseif (is_numeric($productRequest)) {
            return new DataObject(['qty' => $productRequest]);
        }

        return $productRequest;
    }
}
