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

namespace Mageseller\SupplierImport\Model\Data;

use Mageseller\SupplierImport\Api\Data\XitCategoryInterface;

class XitCategory extends \Magento\Framework\Api\AbstractExtensibleObject implements XitCategoryInterface
{

    /**
     * Get xitcategory_id
     * @return string|null
     */
    public function getXitcategoryId()
    {
        return $this->_get(self::XITCATEGORY_ID);
    }

    /**
     * Set xitcategory_id
     * @param string $xitcategoryId
     * @return \Mageseller\SupplierImport\Api\Data\XitCategoryInterface
     */
    public function setXitcategoryId($xitcategoryId)
    {
        return $this->setData(self::XITCATEGORY_ID, $xitcategoryId);
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
     * @return \Mageseller\SupplierImport\Api\Data\XitCategoryInterface
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Mageseller\SupplierImport\Api\Data\XitCategoryExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Mageseller\SupplierImport\Api\Data\XitCategoryExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Mageseller\SupplierImport\Api\Data\XitCategoryExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
