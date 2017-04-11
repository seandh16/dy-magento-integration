<?php

namespace DynamicYield\Integration\Model\Event;


use DynamicYield\Integration\Model\Event;

class SearchEvent extends Event
{
    /**
     * @var string
     */
    protected $searchQuery;

    /**
     * @return string
     */
    function getName()
    {
        return "Keyword Search";
    }

    /**
     * @return string
     */
    function getType()
    {
        return "keyword-search-v1";
    }

    /**
     * @return array
     */
    function getDefaultProperties()
    {
        return [
            'keywords' => null,
        ];
    }

    /**
     * @return array
     */
    function generateProperties()
    {
        return [
            'keywords' => $this->searchQuery
        ];
    }

    /**
     * @param string $searchQuery
     */
    public function setSearchQuery($searchQuery)
    {
        $this->searchQuery = $searchQuery;
    }
}