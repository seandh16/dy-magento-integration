<?php

namespace DynamicYield\Integration\Helper;

use DynamicYield\Integration\Api\Helper\DataInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Category;

class Data extends AbstractHelper implements DataInterface
{
    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var Session
     */
    protected $_quoteSession;

    /**
     * Data constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param Session $quoteSession
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Session $quoteSession
    )
    {
        parent::__construct($context);

        $this->_registry = $registry;
        $this->_quoteSession = $quoteSession;
    }

    /**
     * @return mixed
     */
    public function isEnabled()
    {
        return $this->scopeConfig->getValue(self::ENABLED);
    }

    /**
     * @return mixed
     */
    public function getSectionId()
    {
        return $this->scopeConfig->getValue(self::SECTION_ID);
    }

    /**
     * @return array|bool
     */
    public function getJsIntegration()
    {
        $sectionId = $this->getSectionId();

        if (!$sectionId) {
            return false;
        }

        return [
            "//cdn.dynamicyield.com/api/{$sectionId}/api_static.js",
            "//cdn.dynamicyield.com/api/{$sectionId}/api_dynamic.js"
        ];
    }

    /**
     * @return array
     */
    public function getCurrentContext()
    {
        $request = $this->_request;

        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();

        $name = "{$module}_{$controller}_{$action}";

        $type = "OTHER";
        $data = null;

        switch($name) {
            case 'cms_index_index': {
                $type = 'HOMEPAGE';

                break;
            }

            case 'catalog_product_view': {
                $type = 'PRODUCT';

                /**
                 * @var $product Product
                 */
                $product = $this->_registry->registry('current_product');

                if($product) {
                    $data = [$product->getSku()];
                }

                break;
            }

            case 'catalog_category_view': {
                $type = 'CATEGORY';

                /**
                 * @var $category Category
                 */
                $category = $this->_registry->registry('current_category');

                $data = [];

                if($category) {
                    foreach ($category->getParentCategories() as $parentCategory) {
                        $data[] = $parentCategory->getName();
                    }
                }

                break;
            }

            case 'checkout_index_index':
            case 'checkout_cart_index': {
                $type = 'CART';
                $data = [];

                $quote = $this->_quoteSession->getQuote();

                if($quote) {
                    foreach ($quote->getAllVisibleItems() as $quoteItem) {
                        $data[] = $quoteItem->getSku();
                    }
                }

                break;
            }
        }

        return array_filter([
            'type' => strtoupper($type),
            'data' => $data
        ]);
    }
}