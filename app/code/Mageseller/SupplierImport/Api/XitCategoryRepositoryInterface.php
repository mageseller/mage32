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

namespace Mageseller\SupplierImport\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface XitCategoryRepositoryInterface
{

    /**
     * Save XitCategory
     * @param \Mageseller\SupplierImport\Api\Data\XitCategoryInterface $xitCategory
     * @return \Mageseller\SupplierImport\Api\Data\XitCategoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Mageseller\SupplierImport\Api\Data\XitCategoryInterface $xitCategory
    );

    /**
     * Retrieve XitCategory
     * @param string $xitcategoryId
     * @return \Mageseller\SupplierImport\Api\Data\XitCategoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($xitcategoryId);

    /**
     * Retrieve XitCategory matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Mageseller\SupplierImport\Api\Data\XitCategorySearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete XitCategory
     * @param \Mageseller\SupplierImport\Api\Data\XitCategoryInterface $xitCategory
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Mageseller\SupplierImport\Api\Data\XitCategoryInterface $xitCategory
    );

    /**
     * Delete XitCategory by ID
     * @param string $xitcategoryId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($xitcategoryId);
}
