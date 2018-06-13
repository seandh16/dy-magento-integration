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
use Magento\Store\Model\ScopeInterface as Scope;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Helper\Data as HelperData;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Exception\NoSuchEntityException;





class Data extends AbstractHelper implements HelperInterface
{

    const PRODUCT_GROUPED = "grouped";

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
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ProductRepository
     */
    protected $_productRepository;

    /**
     * @var Int
     */
    protected $_count;


    /**
     * Data constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param Session $quoteSession
     * @param Repository $assetRepo
     * @param ConfigFactory $configFactory
     * @param Queue $queue
     * @param Store $store
     * @param StoreManagerInterface $storeManager
     * @param ProductRepository $productRepository
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Session $quoteSession,
        Repository $assetRepo,
        ConfigFactory $configFactory,
        Queue $queue,
        Store $store,
        StoreManagerInterface $storeManager,
        ProductRepository $productRepository
    )
    {
        parent::__construct($context);

        $this->_registry = $registry;
        $this->_quoteSession = $quoteSession;
        $this->_assetRepo = $assetRepo;
        $this->_queue = $queue;
        $this->_configFactory = $configFactory;
        $this->_store = $store;
        $this->_storeManager = $storeManager;
        $this->_productRepository = $productRepository;
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
     * Return queue
     *
     * @return Queue
     */
    public function getQueue()
    {
        return $this->_queue;
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
        $sectionId = $this->scopeConfig->getValue(self::SECTION_ID,Scope::SCOPE_STORE);

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
     * Return store locale for a store view
     *
     * @param $storeId
     * @return mixed
     */
    public function getStoreLocale($storeId)
    {
        return $this->scopeConfig->getValue(self::LOCALE_ENABLE, Scope::SCOPE_STORE, $storeId) ?
            ($this->scopeConfig->getValue(self::LOCALE_CUSTOM_ENABLE, Scope::SCOPE_STORE, $storeId) ?
                $this->scopeConfig->getValue(self::LOCALE_CUSTOM_LOCALE, Scope::SCOPE_STORE, $storeId) :
                $this->scopeConfig->getValue(self::LOCALE_CUSTOM_SELECT, Scope::SCOPE_STORE, $storeId)) :
            $this->scopeConfig->getValue(\DynamicYield\Integration\Model\Export::LOCALE_CODE, Scope::SCOPE_STORE, $storeId);
    }

    /**p
     * @return array
     */
    public function getCurrentContext()
    {
        $name = $this->getCurrentPageType();
        $language = $this->isMultiLanguage() ? $this->getStoreLocale($this->_storeManager->getStore()->getId()) : null;
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
                    $data[] = $this->getRandomChild($product)->getSku();
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
                $prepareItems = [];
                $quote = $this->_quoteSession->getQuote();

            if($quote) {
                foreach ($quote->getAllItems() as $quoteItem) {
                    if($quoteItem->getProduct()->getTypeId() == "grouped" || $quoteItem->getProduct()->getTypeId() == "bundle") {
                        continue;
                    }

                    if (isset($prepareItems[$quoteItem->getSku()])) {
                        continue;
                    }

                    $prepareItems[$quoteItem->getSku()] = $quoteItem->getSku();
                }

                foreach ($prepareItems as $prepareItem) {
                    $data[] = $prepareItem;
                }
            }

                break;
            }
        }

        $context = array('type' => strtoupper($type), 'lng' => $language, 'data' => $data);

        return array_filter($context,function($var){return !is_null($var);});
    }


    /**
     * Adds prefix to methods to allow duplicate keys in array
     *
     * @param $element
     * @return array|mixed
     */
    public function prepareStructure($element) {
        $preparedElement = [];
        $this->_count = 0;
        $preparedElement = preg_replace_callback('/,"/', array($this, '_addPrefix'), $element);
        return $preparedElement;
    }

    /**
     * Callback to prepare elements by adding a prefix
     *
     * @param $matches
     * @return string
     */
    private function _addPrefix($matches) {
        return ',"' . $this->_count++;
    }

    /**
     * Returns array of prepared theme structure
     *
     * @return array
     */
    public function getCustomStructure() {

        $output = [];
        $structureElements = [
            EventSelectorInterface::LAYERED_NAV_TYPE,
            EventSelectorInterface::LAYERED_NAV_PRICE_VALUE,
            EventSelectorInterface::LAYERED_NAV_REGULAR_VALUE,
            EventSelectorInterface::LAYERED_NAV_SWATCH_VALUE,
            EventSelectorInterface::LAYERED_NAV_SWATCH_IMAGE_VALUE,
            EventSelectorInterface::PRODUCT_SWATCH_TYPE,
            EventSelectorInterface::PRODUCT_SWATCH_VALUE,
            EventSelectorInterface::PRODUCT_SWATCH_IMAGE_VALUE,
            EventSelectorInterface::PRODUCT_ATTRIBUTE_TYPE,
            EventSelectorInterface::PRODUCT_ATTRIBUTE_VALUE,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTION_TYPE,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTION_VALUE,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTION_ALT_VALUE,
            EventSelectorInterface::CATEGORY_SORT_ORDER_VALUE,
            EventSelectorInterface::CATEGORY_SORT_ORDER_DIRECTION,
        ];

        foreach ($structureElements as $structureElement) {
            $output[$structureElement] = $this->prepareStructure($this->scopeConfig
                ->getValue(EventSelectorInterface::CONFIGURATION_PATH . $structureElement));
        }

        return $output;
    }

    /**
     * Returns selectors that trigger filter tracking
     *
     * @return array
     */
    public function getEventSelectors()
    {
        $output = [];
        $eventSelectors = [
            EventSelectorInterface::LAYERED_NAV_TRIGGER,
            EventSelectorInterface::LAYERED_NAV_SWATCH_TRIGGER,
            EventSelectorInterface::PRODUCT_SWATCH_TRIGGER,
            EventSelectorInterface::PRODUCT_ATTRIBUTE_TRIGGER,
            EventSelectorInterface::PRODUCT_CUSTOM_OPTION_TRIGGER,
            EventSelectorInterface::CATEGORY_SORT_ORDER_TRIGGER,
            EventSelectorInterface::CATEGORY_SORT_ORDER_SWITCHER_TRIGGER,
        ];
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

            $html .= '<script type="text/javascript">
                var DY_CUSTOM_STRUCTURE = {' . "\n";
            foreach($this->getCustomStructure() as $key => $element) {
                $html .= '"'.$key .'":'. $element.",\n";
            }

            $html .= "};</script> \n";
            foreach ($this->getJsIntegration() as $item) {
                $html .= '<script type="text/javascript" src="' . $item . '"></script>' . "\n";
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

    /**
     * Check if website has multiple active locales
     *
     * @return bool
     */
    public function isMultiLanguage()
    {
        $locale = [];
        $stores = $this->_storeManager->getStores();
        foreach ($stores as $store) {
            if($this->scopeConfig->getValue(self::LOCALE_ENABLE, Scope::SCOPE_STORE, $store->getId())) {
                return true;
            }
            if (!$store->isActive()) continue;
            $locale[] = $this->scopeConfig->getValue(HelperData::XML_PATH_DEFAULT_LOCALE, Scope::SCOPE_STORE, $store->getId());
        }

        return count(array_unique($locale)) > 1 ? true : false;
    }

    /**
     * Get variation from configurable product
     *
     * @param $product
     * @return Product
     */
    public function getRandomChild($product)
    {
        if($product->getTypeId() == "configurable"){
            $simpleCollection = $product->getTypeInstance()->getUsedProductCollection($product)
                ->addAttributeToSelect('sku','price')
                ->addFilterByRequiredOptions()
                ->addAttributeToFilter('status', Status::STATUS_ENABLED)
                ->addAttributeToFilter('visibility', array('in' => array(
                    Visibility::VISIBILITY_BOTH,
                    Visibility::VISIBILITY_IN_CATALOG
                )));

            foreach($simpleCollection as $simple){
                return $simple;
            }
        }

        return $product;
    }

    /**
     * Check if SKU is valid as per product feed requirements
     *
     * @param $product
     * @return Mixed $variation
     */
    public function validateSku($product)
    {
        try {
            $variation = $this->_productRepository->get($product->getSku());
        } catch (NoSuchEntityException $e) {
            try {
                $variation = $this->_productRepository->get($product->getData('sku'));
            } catch (NoSuchEntityException $e){
                return null;
            }
        }

        if($variation) {
            return $variation;
        }

        return null;
    }

    /**
     * Return sales_quote_item parent product sku
     *
     * @param $item
     * @return bool
     */
    public function getParentItemSku($item)
    {
        if($item->getParentItemId()) {
            return $item->getParentItem()->getProduct()->getData('sku');
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function isFeedSyncEnabled()
    {
        return $this->scopeConfig->getValue(self::PRODUCT_SYNC_ENABLE);
    }

}