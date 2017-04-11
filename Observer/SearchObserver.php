<?php

namespace DynamicYield\Integration\Observer;

use Magento\Framework\Event\Observer;

class SearchObserver extends AbstractObserver
{
    const EVENT_TYPE = 'dyi_search_result_load_after';

    /**
     * @param Observer $observer
     *
     * @return array
     */
    public function dispatch(Observer $observer)
    {
        $param = $this->_request->getParam('q', []);
        $this->_searchEvent->setSearchQuery($param);
        $data = $this->_searchEvent->build();

        return $this->buildResponse([
            'type' => self::EVENT_TYPE,
            'properties' => $data
        ]);
    }
}