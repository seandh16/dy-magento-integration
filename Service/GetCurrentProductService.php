<?php

namespace DynamicYield\Integration\Service;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\SessionFactory as CatalogSessionFactory;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class GetCurrentProductService
 * @package DynamicYield\Integration\Service
 */
class GetCurrentProductService
{
    /**
     * @var ProductInterface
     */
    private $currentProduct;

    /**
     * @var int|null
     */
    private $productId;

    /**
     * @var CatalogSessionFactory
     */
    private $catalogSessionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param CatalogSessionFactory $catalogSessionFactory
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        CatalogSessionFactory $catalogSessionFactory,
        ProductRepositoryInterface $productRepository
    ) {
        $this->catalogSessionFactory = $catalogSessionFactory;
        $this->productRepository = $productRepository;
    }

    /**
     * @return int|null
     */
    public function getProductId()
    {
        if (!$this->productId) {
            $catalogSession = $this->catalogSessionFactory->create();
            $productId = $catalogSession->getData('last_viewed_product_id');
            $this->productId = $productId ? (int)$productId : null;
        }

        return $this->productId;
    }

    /**
     * @return ProductInterface|null
     */
    public function getProduct()
    {
        if (!$this->currentProduct) {
            $productId = $this->getProductId();

            if (!$productId) {
                return null;
            }

            try {
                $this->currentProduct =  $this->productRepository->getById($this->getProductId());
            } catch (NoSuchEntityException $e) {
                return null;
            }
        }

        return $this->currentProduct;
    }
}
