<?php

namespace DynamicYield\Integration\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

class ExcludedCategory implements ArrayInterface
{
    const CATEGORY_LEVEL = '-';

    protected $_categoryCollectionFactory;

    public function __construct(
        CollectionFactory $categoryCollectionFactory
    )
    {
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Get Categories
     *
     * @return array
     */
    public function getCategories() {

        $categories = $this->_categoryCollectionFactory
            ->create()
            ->addAttributeToSelect('name')
            ->addAttributeToSort('path', 'asc')
            ->load()
            ->toArray();

        $categoryList = array();
        foreach ($categories as $catId => $category) {
            if (isset($category['name'])) {
                $categoryList[] = array(
                    'label' => $category['name'],
                    'level'  =>$category['level'],
                    'value' => $catId
                );
            }
        }

        return $categoryList;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();
        $categories = $this->getCategories();

        $options[] = array(
            'label' => 'None',
            'value' => ''
        );

        foreach($categories as $category)
        {
            $prefix = static::CATEGORY_LEVEL;

            for($i=1; $i<$category['level']; $i++) {
                $prefix = $prefix . static::CATEGORY_LEVEL;
            }

            $options[] = array(
                'label' => $prefix . $category['label'],
                'value' => $category['value']
            );
        }

        return $options;
    }
}