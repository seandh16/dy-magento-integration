<?php

namespace DynamicYield\Integration\Plugin;

use Closure;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Controller\Cart\Add;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * AddToCartPlugin constructor
     *
     * @param ManagerInterface $eventManager
     * @param ProductRepository $productRepository
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param Session $checkoutSession
     */
    public function __construct(
        ManagerInterface $eventManager,
        ProductRepository $productRepository,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        Session $checkoutSession
    )
    {
        $this->_eventManager = $eventManager;
        $this->_productRepository = $productRepository;
        $this->_request = $request;
        $this->_storeManager = $storeManager;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @param Add $add
     * @param Closure $proceed
     * @return mixed
     */
    public function aroundExecute(Add $add, Closure $proceed)
    {
        $oldCartItemCount = round($this->_checkoutSession->getQuote()->getItemsQty());
        $productId = $this->_request->getParam('product', []);
        $closure = $proceed();

        if ($closure) {
            if (!is_array($productId) && is_numeric($productId)) {
                /** @var Store $store */
                $store = $this->_storeManager->getStore();
                $newCartItemCount = round($this->_checkoutSession->getQuote()->getItemsQty());

                if ($newCartItemCount > $oldCartItemCount) {
                    try {
                        /** @var Product $product */
                        $product = $this->_productRepository->getById($productId, false, $store->getId());
                        $qty = $this->_request->getParam('qty', 1);

                        $this->_eventManager->dispatch('dyi_add_item_to_cart', [
                            'product' => $product,
                            'qty' => $qty
                        ]);
                    } catch (NoSuchEntityException $exception) {}
                }
            }
        }

        return $closure;
    }
}