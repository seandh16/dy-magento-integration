<?php

namespace DynamicYield\Integration\Helper;

use DynamicYield\Integration\Api\Data\HelperInterface;
use DynamicYield\Integration\Model\Queue;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Category;
use Magento\Framework\View\Asset\Repository;
use Magento\Config\Model\Config\Factory as ConfigFactory;

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

    /**
     * @var Queue
     */
    protected $_queue;

    /**
     * @var ConfigFactory
     */
    protected $_configFactory;

    /**
     * Data constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param Session $quoteSession
     * @param Repository $assetRepo
     * @param ConfigFactory $configFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Session $quoteSession,
        Repository $assetRepo,
        ConfigFactory $configFactory,
        Queue $queue
    )
    {
        parent::__construct($context);

        $this->_registry = $registry;
        $this->_quoteSession = $quoteSession;
        $this->_assetRepo = $assetRepo;
        $this->_queue = $queue;
        $this->_configFactory = $configFactory;
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
     * @return array
     */
    public function getDefaultAttributes()
    {
        return [
            'name',
            'sku',
            'url_path',
            'price',
            'image'
        ];
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
            $this->getViewFileUrl('DynamicYield_Integration::js/storage.js'),
            $this->getViewFileUrl('DynamicYield_Integration::js/lib/xhook.min.js'),
            $this->getViewFileUrl('DynamicYield_Integration::js/hook.js'),
            $this->getViewFileUrl('DynamicYield_Integration::js/tracking.js')
        ];
    }

    /**
     * @param $event
     * @return string
     */
    public function addEvent($event)
    {
        if (isset($event['properties'])) {
            $eventData = json_encode($event['properties']);

            return "<script type=\"text/javascript\">
                try {
                    DY.API('event', " . $eventData . ");
                } catch(e) {
                    MGB.StorageUtils.setData(" . $eventData . ");
                }
            </script>\n";
        }
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
            $html .= '<script type="text/javascript">
                var DY_HEADER_NAME = ("' . $this->getEventName() . '").toLowerCase(),
                    DY_STORAGE_URL = "' . $this->_urlBuilder->getUrl('dyIntegration/storage/index') . '";
                
                window.MGB = window.MGB || {};
            </script>' . "\n";
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

    /**
     * Set custom config
     *
     * @param $configPath
     * @param $configValue
     * @param null $website
     * @param null $store
     * @return mixed
     * @throws LocalizedException
     */
    public function setCustomConfig($configPath, $configValue, $website = null, $store = null)
    {
        if (empty($configPath)) {
            throw new LocalizedException(
                new Phrase('Config path can not be empty')
            );
        }

        $configPath = explode('/', $configPath, 3);

        if (count($configPath) != 3) {
            throw new LocalizedException(
                new Phrase('Incorrect config path')
            );
        }

        return $this->_configFactory->create(['data' => [
            'section' => $configPath[0],
            'website' => $website,
            'store' => $store,
            'groups' => [
                $configPath[1] => [
                    'fields' => [
                        $configPath[2] => [
                            'value' => $configValue
                        ]
                    ]
                ]
            ]
        ]])->save();
    }
}