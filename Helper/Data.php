<?php

namespace DynamicYield\Integration\Helper;

use DynamicYield\Integration\Api\Data\EventSelectorInterface;
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
use Magento\Framework\Locale\Resolver as Store;


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
     * @var Store
     */
    protected $_store;

    /**
     * Data constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param Session $quoteSession
     * @param Repository $assetRepo
     * @param ConfigFactory $configFactory
     * @param Store $store
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Session $quoteSession,
        Repository $assetRepo,
        ConfigFactory $configFactory,
        Queue $queue,
        Store $store
    )
    {
        parent::__construct($context);

        $this->_registry = $registry;
        $this->_quoteSession = $quoteSession;
        $this->_assetRepo = $assetRepo;
        $this->_queue = $queue;
        $this->_configFactory = $configFactory;
        $this->_store = $store;
    }

    /**
     * @return mixed
     */
    public function isEnabled()
    {
        return !empty($this->getSectionId());
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
            $this->getViewFileUrl('DynamicYield_Integration::js/dy_tracker.js')
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
                    DY.API('event', " . $eventData . "); 
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
     * @return string
     */
    public function getCurrentPageType()
    {
        $module = $this->_request->getModuleName();
        $controller = $this->_request->getControllerName();
        $action = $this->_request->getActionName();

        return strtolower("{$module}_{$controller}_{$action}");
    }

    /**
     * @return array
     */
    public function getCurrentContext()
    {
        $name = $this->getCurrentPageType();
        $language = $this->_store->getLocale();
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
                        if($quoteItem->getProduct()->getTypeId() == "grouped" || $quoteItem->getProduct()->getTypeId() == "bundle") continue;
                        $data[] = $quoteItem->getProduct()->getData("sku");
                    }
                }

                break;
            }
        }

        return array_filter([
            'type' => strtoupper($type),
            'lng' => $language,
            'data' => $data
        ]);
    }

    /**
     * @return array
     */
    public function getEventSelectors()
    {
        $eventSelectors = [
            EventSelectorInterface::LAYERED_NAV_BLOCK,
            EventSelectorInterface::LAYERED_NAV_CONTENT,
            EventSelectorInterface::LAYERED_NAV_TRIGGER,
            EventSelectorInterface::LAYERED_NAV_CONTAINER,
            EventSelectorInterface::LAYERED_NAV_FILTER_TITLE,
            EventSelectorInterface::LAYERED_NAV_SWATCH_OPTION,
            EventSelectorInterface::LAYERED_NAV_SWATCH_TITLE,
            EventSelectorInterface::LAYERED_NAV_SWATCH_DATA_TITLE,
            EventSelectorInterface::LAYERED_NAV_FILTER_PRICE,
            EventSelectorInterface::LAYERED_NAV_FILTER_ITEM_COUNT,
            EventSelectorInterface::TOOLBAR_SORTER_BLOCK,
            EventSelectorInterface::TOOLBAR_SORTER_TYPE,
            EventSelectorInterface::TOOLBAR_SORTER_ORDER,
            EventSelectorInterface::TOOLBAR_SORTER_VALUE,
            EventSelectorInterface::PRODUCT_OPTIONS_CONTAINER,
            EventSelectorInterface::PRODUCT_OPTIONS_ATTRIBUTE,
            EventSelectorInterface::PRODUCT_OPTIONS_ATTRIBUTE_PARENT,
            EventSelectorInterface::PRODUCT_OPTIONS_ATTRIBUTE_NAME_LABEL,
            EventSelectorInterface::PRODUCT_OPTIONS_ATTRIBUTE_NAME_CONTAINER,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTIONS_CONTAINER,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTIONS_TYPE_SELECT,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTIONS_TYPE_INPUT,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTIONS_TYPE_RADIO,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTIONS_TYPE_CHECKBOX,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTIONS_SWATCH_CONTAINER,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTIONS_SWATCH_SELECT,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTIONS_SWATCH_ATTRIBUTE_VALUE,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTIONS_SWATCH_SELECTED_ATTRIBUTE,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTIONS_SWATCH_ATTRIBUTE_PARENT,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTIONS_CONTROL,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTIONS_CONTROL_PARENT,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTIONS_LABEL,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTIONS_LABEL_CONTAINER,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTIONS_SELECT_MULTIPLE
        ];

        $output = [];

        foreach ($eventSelectors as $eventSelector) {
            $output[$eventSelector] = $this->scopeConfig
                ->getValue(EventSelectorInterface::CONFIGURATION_PATH . $eventSelector);
        }

        return $output;
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
                var DY_SETTINGS = {
                    "headerName": ("' . $this->getEventName() . '").toLowerCase(),
                    "currentPage": "' . $this->getCurrentPageType() . '",
                    "eventSelectors": ' . json_encode($this->getEventSelectors()) . '
                };  
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