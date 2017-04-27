<?php

namespace DynamicYield\Integration\Model\Config\Source;

use DynamicYield\Integration\Helper\Feed as FeedHelper;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Option\ArrayInterface;
use Magento\Config\Model\Config\Factory as ConfigFactory;
use Magento\Framework\Phrase;

abstract class AbstractProductAttribute implements ArrayInterface
{
    /**
     * @var array
     */
    protected $_defaultAttributes = [
        'name',
        'sku',
        'url_path',
        'price',
        'image'
    ];

    /**
     * @var Attribute
     */
    protected $_attribute;

    /**
     * @var ConfigFactory
     */
    protected $_configFactory;

    /**
     * @var FeedHelper
     */
    protected $_feedHelper;

    /**
     * ProductAttribute constructor
     *
     * @param Attribute $attribute
     * @param ConfigFactory $configFactory
     * @param FeedHelper $feedHelper
     */
    public function __construct(
        Attribute $attribute,
        ConfigFactory $configFactory,
        FeedHelper $feedHelper
    )
    {
        $this->_attribute = $attribute;
        $this->_configFactory = $configFactory;
        $this->_feedHelper = $feedHelper;
    }

    /**
     * @return mixed
     */
    abstract function getAttributes();

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = $this->getAttributes();

        $data = [];

        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            $data[] = [
                'value' => $attribute->getAttributeCode(),
                'label' => $attribute->getAttributeCode() . " ({$attribute->getFrontend()->getLabel()})"
            ];
        }

        return $data;
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
    protected function setCustomConfig($configPath, $configValue, $website = null, $store = null)
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