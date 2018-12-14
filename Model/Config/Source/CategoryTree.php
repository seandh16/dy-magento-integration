<?php

namespace DynamicYield\Integration\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

class CategoryTree implements ArrayInterface
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
            ->addAttributeToFilter('level', array('eq' => '1'))
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
            'label' => '',
            'value' => ''
        );

        foreach($categories as $category)
        {
            $options[] = array(
                'label' => $category['label'],
                'value' => $category['value']
            );
        }

        return $options;
    }
}