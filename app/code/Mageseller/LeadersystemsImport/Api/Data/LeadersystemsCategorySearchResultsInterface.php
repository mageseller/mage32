<?php
/**
 * A Magento 2 module named Mageseller/LeadersystemsImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/LeadersystemsImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\LeadersystemsImport\Api\Data;

interface LeadersystemsCategorySearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get LeadersystemsCategory list.
     *
     * @return \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface[]
     */
    public function getItems();

    /**
     * Set name list.
     *
     * @param  \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
