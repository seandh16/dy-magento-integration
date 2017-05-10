<?php

namespace DynamicYield\Integration\Model\Config\Backend;

use DynamicYield\Integration\Api\Data\ProductFeedInterface;
use DynamicYield\Integration\Helper\Data as Helper;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

abstract class AbstractUpdateRate extends Value
{
    const CRON_TEMPLATE = 'min h d m w';

    /**
     * @var array
     */
    protected $_units = [
        'min' => [0, 59, 1],
        'h' => [0, 23, 60],
        'd' => [1, 31, 1440],
        'w' => [0, 6, 10080],
        'm' => [1, 12, 43200]
    ];

    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @var ScopeConfigInterface
     */
    protected $_config;

    /**
     * AbstractUpdateRate constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param Helper $helper
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Helper $helper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_helper = $helper;
        $this->_config = $config;
    }

    /**
     * @return mixed
     */
    abstract function getTime();

    /**
     * @return mixed
     */
    abstract function getType();

    /**
     * @return Value
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        $label = $this->getData('field_config/label');
        $value = (int)$this->getTime();
        $type = $this->getType();
        $match = null;

        foreach ($this->_units as $unit) {
            if ($unit[2] == $type) {
                $match = $unit;
            }
        }

        if (!$match) {
            throw new ValidatorException(__('Invalid update rate selected.'));
        }

        list($min, $max, $unit) = $match;
        $input = $value + 0;

        if ($min === 0) {
            $min = 1;
            $max += 1;
        }

        if ($min > $input || $input > $max) {
            throw new ValidatorException(__($label . ' must be between %1 and %2.', $min, $max));
        }

        return parent::beforeSave();
    }

    /**
     * @return Value
     */
    public function afterSave()
    {
        $value = [
            (int)$this->getTime(),
            $this->getType()
        ];

        $minutes = array_reduce($value, function ($carry, $value) {
            return $carry * $value;
        }, 1);

        $cronExpr = [
            'min' => '*',
            'h' => '*',
            'd' => '*',
            'w' => '*',
            'm' => '*'
        ];

        $units = $this->_units;
        arsort($units, SORT_NUMERIC);

        foreach ($units as $k => $unit) {
            list($min, $max, $division) = $unit;
            $number = round($minutes / $division);

            if ($minutes >= $division && $number < $max && $number >= $min) {
                $cronExpr[$k] = '*/' . $number;
            } else {
                $cronExpr[$k] = ($min > 0 ? '*/' . $min : ($minutes > $division ? '0' : '*'));
            }
        }

        $cronExprString = str_replace(array_keys($cronExpr), $cronExpr, self::CRON_TEMPLATE);

        $this->_helper->setCustomConfig(ProductFeedInterface::CRON_SCHEDULE_PATH, $cronExprString);

        return parent::afterSave();
    }
}