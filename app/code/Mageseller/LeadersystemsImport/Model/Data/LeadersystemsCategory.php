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

namespace Mageseller\LeadersystemsImport\Model\Data;

use Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface;

class LeadersystemsCategory extends \Magento\Framework\Api\AbstractExtensibleObject implements LeadersystemsCategoryInterface
{

    /**
     * Get leadersystemscategory_id
     * @return string|null
     */
    public function getLeadersystemscategoryId()
    {
        return $this->_get(self::XITCATEGORY_ID);
    }

    /**
     * Set leadersystemscategory_id
     * @param string $leadersystemscategoryId
     * @return \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface
     */
    public function setLeadersystemscategoryId($leadersystemscategoryId)
    {
        return $this->setData(self::XITCATEGORY_ID, $leadersystemscategoryId);
    }

    /**
     * Get name
     * @return string|null
     */
    public function getName()
    {
        return $this->_get(self::NAME);
    }

    /**
     * Set name
     * @param string $name
     * @return \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
