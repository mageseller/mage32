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

interface LeadersystemsCategoryInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const NAME = 'name';
    const XITCATEGORY_ID = 'leadersystemscategory_id';

    /**
     * Get leadersystemscategory_id
     * @return string|null
     */
    public function getLeadersystemscategoryId();

    /**
     * Set leadersystemscategory_id
     * @param string $leadersystemscategoryId
     * @return \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface
     */
    public function setLeadersystemscategoryId($leadersystemscategoryId);

    /**
     * Get name
     * @return string|null
     */
    public function getName();

    /**
     * Set name
     * @param string $name
     * @return \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface
     */
    public function setName($name);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryExtensionInterface $extensionAttributes
    );
}
