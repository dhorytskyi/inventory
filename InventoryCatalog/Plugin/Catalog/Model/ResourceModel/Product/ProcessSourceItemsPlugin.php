<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Process source items after product save.
 */
class ProcessSourceItemsPlugin
{
    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param PublisherInterface $publisher
     */
    public function __construct(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * Asynchronously cleanup source items in case product has changed type or sku.
     *
     * @param Product $subject
     * @param Product $result
     * @param AbstractModel $product
     * @return Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(Product $subject, Product $result, AbstractModel $product): Product
    {
        if ($this->isCleanupNeeded($product)) {
            $this->publisher->publish(
                'async.inventory.source.items.cleanup',
                [
                    [
                        (string)$product->getOrigData('sku')
                    ]
                ]
            );
        }

        return $result;
    }

    /**
     * Check if source items for given product should be deleted.
     *
     * @param AbstractModel $product
     * @return bool
     */
    private function isCleanupNeeded(AbstractModel $product): bool
    {
        $origSku = $product->getOrigData('sku');
        $origType = $product->getOrigData('type_id');

        return $origType !== null && $origType !== $product->getTypeId()
            || $origSku !== null && $origSku !== $product->getSku();
    }
}
