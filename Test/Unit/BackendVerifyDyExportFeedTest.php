<?php

namespace DynamicYield\Integration\Test\Unit;

use DynamicYield\Integration\Helper\Feed;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class BackendVerifyDyExportFeedTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Feed $feedHelper
     */
    protected $feedHelper;

    /**
     * @var string
     */
    protected $expectedMessage;

    protected $_baseAttributes = [
        'name',
        'url',
        'sku',
        'group_id',
        'price',
        'in_stock',
        'categories',
        'image_url'
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->feedHelper = $this->objectManagerHelper->getObject(Feed::class);
    }

    public function testGetMessage()
    {
        $this->assertEquals($this->_baseAttributes, $this->feedHelper->getBaseAttributes());
    }
}
