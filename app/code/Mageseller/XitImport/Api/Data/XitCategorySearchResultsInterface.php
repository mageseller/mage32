<?php
/**
 * A Magento 2 module named Mageseller/XitImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/XitImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\XitImport\Api\Data;

interface XitCategorySearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get XitCategory list.
     * @return \Mageseller\XitImport\Api\Data\XitCategoryInterface[]
     */
    public function getItems();

    /**
     * Set name list.
     * @param \Mageseller\XitImport\Api\Data\XitCategoryInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
