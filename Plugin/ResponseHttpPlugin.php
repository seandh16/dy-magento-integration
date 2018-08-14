<?php

namespace DynamicYield\Integration\Plugin;

use DynamicYield\Integration\Helper\Data;
use Magento\Framework\App\Response\Http;

class ResponseHttpPlugin
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * ResponseHttpPlugin constructor
     *
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    )
    {
        $this->_helper = $helper;
    }

    /**
     * @param Http $subject
     * @param $value
     * @return array
     */
    public function beforeAppendBody(Http $subject, $value)
    {
        if (is_callable([$subject, 'isAjax']) && $subject->isAjax()) {
            return [$value];
        }

        if ($this->_helper->isEnabled() && $this->_helper->getJsIntegration()) {
            preg_match('/<head[^>]*>/', $value, $match);

            if (isset($match[0])) {
                $value = str_ireplace($match[0], $match[0] . "\n" . $this->_helper->getHtmlMarkup(), $value);
            }
        }

        return [$value];
    }
}