<?php

namespace DynamicYield\Integration\Helper;

use DynamicYield\Integration\Api\Data\HelperInterface;
use DynamicYield\Integration\Model\Queue;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Category;
use Magento\Framework\View\Asset\Repository;

class Data extends AbstractHelper implements HelperInterface
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
     * @var Repository
     */
    protected $_assetRepo;

    protected $_queue;

    /**
     * Data constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param Session $quoteSession
     * @param Repository $assetRepo
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Session $quoteSession,
        Repository $assetRepo,
        Queue $queue
    )
    {
        parent::__construct($context);

        $this->_registry = $registry;
        $this->_quoteSession = $quoteSession;
        $this->_assetRepo = $assetRepo;
        $this->_queue = $queue;
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
     * @return mixed
     */
    public function getEventName()
    {
        return $this->scopeConfig->getValue(self::EVENT_NAME);
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
            "//cdn.dynamicyield.com/api/{$sectionId}/api_dynamic.js",
            $this->getViewFileUrl('DynamicYield_Integration::js/lib/xhook.min.js'),
            $this->getViewFileUrl('DynamicYield_Integration::js/hook.js')
        ];
    }

    /**
     * @param $event
     * @return string
     */
    public function addEvent($event)
    {
        return "<script>DY.API('event', " . json_encode($event['properties']) . ");</script>\n";
    }

    /**
     * @param $fileId
     * @param array $params
     * @return string
     */
    protected function getViewFileUrl($fileId, array $params = [])
    {
        try {
            $params = array_merge(['_secure' => $this->_request->isSecure()], $params);
            return $this->_assetRepo->getUrlWithParams($fileId, $params);
        } catch (LocalizedException $e) {
            $this->_logger->critical($e);
            return $this->_urlBuilder->getUrl('', ['_direct' => 'core/index/notFound']);
        }
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

    /**
     * @return string
     */
    public function getHtmlMarkup()
    {
        $html = '';

        if ($this->isEnabled() && $this->getJsIntegration()) {
            $html .= '<script type="text/javascript">// <![CDATA[
                window.DY = window.DY || {};
                DY.recommendationContext = ' . json_encode($this->getCurrentContext()) . ';
            // ]]>
            </script>' . "\n";
            $html .= '<script type="text/javascript">var DY_HEADER_NAME = ("' . $this->getEventName() . '").toLowerCase();</script>' . "\n";
            foreach ($this->getJsIntegration() as $item) {
                $html .= '<script type="text/javascript" src="' . $item . '"></script>' . "\n";
            }

            $events = $this->_queue->getCollection();

            if (!empty($events) || is_array($events)) {
                foreach ($events as $event) {
                    $html .= $this->addEvent($event);
                }

                $this->_queue->clearQueue();
            }

        }

        return $html;
    }
}