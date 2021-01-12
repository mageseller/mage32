<?php
/**
 * A Magento 2 module named Mageseller/SupplierImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/SupplierImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\SupplierImport\Api\Data;

interface XitCategorySearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get XitCategory list.
     * @return \Mageseller\SupplierImport\Api\Data\XitCategoryInterface[]
     */
    public function getItems();

    /**
     * Set name list.
     * @param \Mageseller\SupplierImport\Api\Data\XitCategoryInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
