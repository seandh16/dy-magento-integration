<?php

namespace DynamicYield\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Psr\Log\LoggerInterface;

class ServerCookie implements ObserverInterface
{
    const DY_COOKIE = '_dyid';
    const SERVER_DY_COOKIE = '_dyid_server';

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        LoggerInterface $logger
    )
    {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(Observer $observer)
    {
        $dyCookie = $this->cookieManager->getCookie(self::DY_COOKIE);
        $newDyCookie = $this->cookieManager->getCookie(self::SERVER_DY_COOKIE);

        if ($dyCookie && !$newDyCookie) {
            $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
            $publicCookieMetadata->setDurationOneYear();
            $publicCookieMetadata->setPath('/');

            try {
                $this->cookieManager->setPublicCookie(self::SERVER_DY_COOKIE, $dyCookie, $publicCookieMetadata);
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
            }
        }
    }
}
