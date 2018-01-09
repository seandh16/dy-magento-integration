<?php

namespace DynamicYield\Integration\Block;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\AbstractBlock;
use DynamicYield\Integration\Helper\Data;

class EventsHead extends AbstractBlock {

    protected $_helper;

    /**
     * HeartBeat constructor
     *
     * @param Context $context
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        Data $helper
    )
    {
        $this->_helper = $helper;
        parent::__construct($context);
    }

    /**
     *
     * @return string
     */
    protected function _toHtml()
    {
        $events = $this->_helper->getQueue()->getCollection();

        $html = "";
        if (!empty($events) || is_array($events)) {
            foreach ($events as $event) {
                $html .= $this->_helper->addEvent($event);
            }

            $this->_helper->getQueue()->clearQueue();
        }

        return $html . "that";
    }

}