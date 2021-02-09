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

namespace Mageseller\IngrammicroImport\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface IngrammicroCategoryRepositoryInterface
{

    /**
     * Save IngrammicroCategory
     *
     * @param  \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface $ingrammicroCategory
     * @return \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface $ingrammicroCategory
    );

    /**
     * Retrieve IngrammicroCategory
     *
     * @param  string $ingrammicrocategoryId
     * @return \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($ingrammicrocategoryId);

    /**
     * Retrieve IngrammicroCategory matching the specified criteria.
     *
     * @param  \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategorySearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete IngrammicroCategory
     *
     * @param  \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface $ingrammicroCategory
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface $ingrammicroCategory
    );

    /**
     * Delete IngrammicroCategory by ID
     *
     * @param  string $ingrammicrocategoryId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($ingrammicrocategoryId);
}
