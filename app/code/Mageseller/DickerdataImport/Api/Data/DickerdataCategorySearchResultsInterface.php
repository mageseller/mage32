<?php
/**
 * A Magento 2 module named Mageseller/DickerdataImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/DickerdataImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\DickerdataImport\Api\Data;

interface DickerdataCategorySearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get DickerdataCategory list.
     *
     * @return \Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterface[]
     */
    public function getItems();

    /**
     * Set name list.
     *
     * @param  \Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
