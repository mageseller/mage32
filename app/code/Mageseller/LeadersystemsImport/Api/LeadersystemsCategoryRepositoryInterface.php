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

namespace Mageseller\LeadersystemsImport\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface LeadersystemsCategoryRepositoryInterface
{

    /**
     * Save LeadersystemsCategory
     * @param \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface $leadersystemsCategory
     * @return \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface $leadersystemsCategory
    );

    /**
     * Retrieve LeadersystemsCategory
     * @param string $leadersystemscategoryId
     * @return \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($leadersystemscategoryId);

    /**
     * Retrieve LeadersystemsCategory matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategorySearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete LeadersystemsCategory
     * @param \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface $leadersystemsCategory
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface $leadersystemsCategory
    );

    /**
     * Delete LeadersystemsCategory by ID
     * @param string $leadersystemscategoryId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($leadersystemscategoryId);
}
