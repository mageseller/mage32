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

namespace Mageseller\DickerdataImport\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface DickerdataCategoryRepositoryInterface
{

    /**
     * Save DickerdataCategory
     * @param \Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterface $dickerdataCategory
     * @return \Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterface $dickerdataCategory
    );

    /**
     * Retrieve DickerdataCategory
     * @param string $dickerdatacategoryId
     * @return \Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($dickerdatacategoryId);

    /**
     * Retrieve DickerdataCategory matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Mageseller\DickerdataImport\Api\Data\DickerdataCategorySearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete DickerdataCategory
     * @param \Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterface $dickerdataCategory
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterface $dickerdataCategory
    );

    /**
     * Delete DickerdataCategory by ID
     * @param string $dickerdatacategoryId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($dickerdatacategoryId);
}
