<?php

namespace DynamicYield\Integration\Block\Adminhtml\Form\Field;

use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Select;
use Magento\Framework\Escaper;

class SyncRate extends Select
{
    /**
     * SyncRate constructor
     *
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param array $data
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        array $data = []
    )
    {
        parent::__construct(
            $factoryElement,
            $factoryCollection,
            $escaper,
            $data
        );

        $this->setType('sync-rate');
        $this->_prepareOptions();
    }

    /**
     * Get the element Html.
     *
     * @return string
     */
    public function getElementHtml()
    {
        $html = '';
        if ($this->getBeforeElementHtml()) {
            $html .= '<label class="addbefore" for="' .
                $this->getHtmlId() .
                '">' .
                $this->getBeforeElementHtml() .
                '</label>';
        }

        $value = explode(',', $this->getValue());

        if (!is_array($value)) {
            $value = [$value];
        }

        $syncValue = isset($value[0]) ? 'value="' . $value[0] . '"' : '';

        $html .= '<input id="' . $this->getHtmlId() . '" 
            type="number" name="' . $this->getName() . '[]" ' . $syncValue .  ' style="float: left; width: 60px;" ' . $this->_getUiId() . ' />';

        $html .= '<select name="' . $this->getName() . '[]" 
            style="display: block; width: calc(100% - 80px); margin-left: 80px;">';

        if ($values = $this->getValues()) {
            foreach ($values as $key => $option) {
                if (!is_array($option)) {
                    $html .= $this->_optionToHtml(['value' => $key, 'label' => $option], $value);
                } elseif (is_array($option['value'])) {
                    $html .= '<optgroup label="' . $option['label'] . '">' . "\n";
                    foreach ($option['value'] as $groupItem) {
                        $html .= $this->_optionToHtml($groupItem, $value);
                    }
                    $html .= '</optgroup>' . "\n";
                } else {
                    $html .= $this->_optionToHtml($option, $value);
                }
            }
        }

        $html .= '</select>' . "\n";
        if ($this->getAfterElementHtml()) {
            $html .= '<label class="addafter" for="' .
                $this->getHtmlId() .
                '">' .
                "\n{$this->getAfterElementHtml()}\n" .
                '</label>' .
                "\n";
        }
        return $html;
    }
}