<?php
/**
 * A Magento 2 module named Mageseller/IngrammicroImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/IngrammicroImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\IngrammicroImport\Api\Data;

interface IngrammicroCategorySearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get IngrammicroCategory list.
     *
     * @return \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface[]
     */
    public function getItems();

    /**
     * Set name list.
     *
     * @param  \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
