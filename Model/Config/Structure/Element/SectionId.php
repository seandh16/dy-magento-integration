<?php

namespace DynamicYield\Integration\Model\Config\Structure\Element;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Config\Model\Config\Structure\Element\Field;
use Magento\Framework\Phrase;

class SectionId extends Field implements CommentInterface
{
    /**
     * @param string $elementValue
     * @return Phrase
     */
    public function getCommentText($elementValue)
    {
        return __('Your site ID is listed under <a href="%1" target="_blank">Sites</a> in your Dynamic Yield account.', 'https://adm.dynamicyield.com/users/sign_in#/settings/sections');
    }
}