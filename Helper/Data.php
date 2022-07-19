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
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Category;
use Magento\Framework\View\Asset\Repository;
use Magento\Config\Model\Config\Factory as ConfigFactory;
use Magento\Framework\Locale\Resolver as Store;
use Magento\Store\Model\ScopeInterface as Scope;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Helper\Data as HelperData;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Exception\NoSuchEntityException;
use DynamicYield\Integration\Model\Config\Source\IntegrationType;
use DynamicYield\Integration\Model\Export;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use \Magento\Framework\App\ProductMetadataInterface;
use DynamicYield\Integration\Service\GetCurrentCategoryService;
use DynamicYield\Integration\Service\GetCurrentProductService;

/**
 * Class Data
 * @package DynamicYield\Integration\Helper
 */
class Data extends AbstractHelper implements HelperInterface
{

    const PRODUCT_GROUPED = 'grouped';
    const PRODUCT_CONFIGURABLE = 'configurable';

    /**
     * @var GetCurrentProductService
     */
    protected $_currentProductService;

    /**
     * @var GetCurrentCategoryService
     */
    protected $_currentCategoryService;

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
     * Category collection factory
     *
     * @var CategoryCollectionFactory
     */
    protected $_categoryCollectionFactory;

    /**
     * @var ProductMetadataInterface
     */
    protected $_metaData;


    /**
     * Data constructor
     *
     * @param Context $context
     * @param Session $quoteSession
     * @param Repository $assetRepo
     * @param ConfigFactory $configFactory
     * @param Queue $queue
     * @param Store $store
     * @param StoreManagerInterface $storeManager
     * @param ProductRepository $productRepository
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @paarm GetCurrentProductService $getCurrentProductService
     * @paarm GetCurrentCategoryService $getCurrentCategoryService
     */
    public function __construct(
        Context $context,
        Session $quoteSession,
        Repository $assetRepo,
        ConfigFactory $configFactory,
        Queue $queue,
        Store $store,
        StoreManagerInterface $storeManager,
        ProductRepository $productRepository,
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductMetadataInterface $metaData,
        GetCurrentProductService $getCurrentProductService,
        GetCurrentCategoryService $getCurrentCategoryService
    )
    {
        parent::__construct($context);

        $this->_quoteSession = $quoteSession;
        $this->_assetRepo = $assetRepo;
        $this->_queue = $queue;
        $this->_configFactory = $configFactory;
        $this->_store = $store;
        $this->_storeManager = $storeManager;
        $this->_productRepository = $productRepository;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_metaData = $metaData;
        $this->_currentProductService = $getCurrentProductService;
        $this->_currentCategoryService = $getCurrentCategoryService;
    }

    /**
     * @return mixed
     */
    public function getMagentoEdition()
    {
        return $this->_metaData->getEdition();
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
            "//".$this->getCDN()."/api/{$sectionId}/api_static.js",
            "//".$this->getCDN()."/api/{$sectionId}/api_dynamic.js",
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
                                $this->scopeConfig->getValue(Export::LOCALE_CODE, Scope::SCOPE_STORE, $storeId);
    }

    /**
     * Get default CDN
     *
     * @return string
     */
    public function getDefaultCDN()
    {
        return static::DEFAULT_CDN;
    }

    /**
     * Get Europe CDN
     *
     * @return string
     */
    public function getEuropeCDN()
    {
        return static::EUROPE_CDN;
    }

    /**
     * Get custom CDN
     *
     * @return mixed
     */
    public function getCustomCDN()
    {
        return $this->scopeConfig->getValue(self::CONF_CUSTOM_CDN);
    }

    /**
     * Is EU account enabled
     *
     * @return mixed
     */
    public function isEuropeAccount()
    {
        return $this->scopeConfig->getValue(self::CONF_ENABLE_EUROPE_ACCOUNT);
    }

    /**
     * Is CDN integration enabled
     *
     * @return mixed
     */
    public function isCDNIntegration()
    {
        return $this->scopeConfig->getValue(self::CONF_ENABLE_CDN_INTEGRATION) != IntegrationType::CDN_DISABLED;
    }

    /**
     * Is European CDN Integration enabled
     *
     * @return bool
     */
    public function isEuropeCDNIntegration()
    {
        return $this->scopeConfig->getValue(self::CONF_ENABLE_CDN_INTEGRATION) == IntegrationType::CDN_EUROPEAN;
    }

    /**
     * Get CDN url
     *
     * @return mixed|string
     */
    public function getCDN()
    {
        if($this->isEuropeAccount()) {
            return $this->getEuropeCDN();
        } elseif($this->isCDNIntegration()) {
            return $this->getCustomCDN();
        }

        return $this->getDefaultCDN();
    }

    /**
     * Get Excluded Category IDs
     *
     * @return array
     */
    public function getExcludedCategories()
    {
        return $this->scopeConfig->getValue(self::CONF_EXCLUDED_CATEGORIES);
    }

    /**
     * Get Category Root
     *
     * @param $storeId
     * @return array
     */
    public function getCategoryTree($storeId = 0)
    {
        return $this->scopeConfig->getValue(self::CONF_CATEGORY_ROOT,Scope::SCOPE_STORE,$storeId);
    }

    /**
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
                $product = $this->_currentProductService->getProduct();

                if ($product) {
                    $data[] = $this->replaceSpaces($this->getRandomChild($product)->getSku());
                }

                break;
            }

            case 'catalog_category_view': {
                $type = 'CATEGORY';

                /**
                 * @var $category Category
                 */
                $category = $this->_currentCategoryService->getCategory();

                $data = [];

                if ($category) {
                    foreach ($this->getParentCategories($category) as $parentCategory) {
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
                    /**
                     * Skip parent product types
                     */
                    if(in_array($quoteItem->getProduct()->getTypeId(),array(static::PRODUCT_GROUPED, static::PRODUCT_CONFIGURABLE, Type::TYPE_BUNDLE))) {
                        continue;
                    }

                    $sku = $this->replaceSpaces($quoteItem->getSku());

                    if (isset($prepareItems[$sku]) || !$this->validateSku($quoteItem)) {
                        continue;
                    }

                    $prepareItems[$sku] = $sku;
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


    public function getDefaultStoreView()
    {
        return $this->scopeConfig->getValue(self::CONF_DEFAULT_STORE,ScopeInterface::SCOPE_STORE) ?: $this->_storeManager->getStore();
    }

    /**
     * Return parent categories of category
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return \Magento\Framework\DataObject[]
     */
    public function getParentCategories($category)
    {
        $pathIds = array_reverse(explode(',', $category->getPathInStore() ?? ''));
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $categories */
        $categories = $this->_categoryCollectionFactory->create();
        $categories->setStore(
            $this->getDefaultStoreView()
        )->addAttributeToSelect(
            'name'
        )->addAttributeToSelect(
            'url_key'
        )->addFieldToFilter(
            'entity_id',
            ['in' => $pathIds]
        )->addFieldToFilter(
            'is_active',
            1
        );
        $categories->getSelect()->order(new \Zend_Db_Expr('FIELD(e.entity_id,' . implode(',', $pathIds).')'));

        return $categories->load()->getItems();
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

            $html .= '
            <link rel="preconnect" href="//'.$this->getCDN().'">
            <link rel="preconnect" href="//st.dynamicyield.com">
            <link rel="preconnect" href="//rcom.dynamicyield.com">
            <link rel="dns-prefetch" href="//'.$this->getCDN().'">
            <link rel="dns-prefetch" href="//st.dynamicyield.com">
            <link rel="dns-prefetch" href="//rcom.dynamicyield.com">
            ' . "\n";

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
                ->addAttributeToFilter('status', Status::STATUS_ENABLED);

            foreach($simpleCollection as $simple){
                return $simple;
            }
        }

        return $product;
    }

    /**
     * Check if SKU is valid
     * No custom options
     *
     * @param $product
     * @return Mixed $variation
     */
    public function validateSku($product)
    {
        try {
            $variation = $this->_productRepository->get($product->getSku());
            if($variation) {
                return true;
            }
        } catch (NoSuchEntityException $e) {
            return null;
        }
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

    /**
     * @param $string
     * @return string|string[]|null
     */
    public function replaceSpaces($string)
    {
        return preg_replace('/\s+/', '_', $string);
    }

}
