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

namespace Mageseller\DickerdataImport\Model\Data;

use Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterface;

class DickerdataCategory extends \Magento\Framework\Api\AbstractExtensibleObject implements DickerdataCategoryInterface
{

    /**
     * Get dickerdatacategory_id
     * @return string|null
     */
    public function getDickerdatacategoryId()
    {
        return $this->_get(self::XITCATEGORY_ID);
    }

    /**
     * Set dickerdatacategory_id
     * @param string $dickerdatacategoryId
     * @return \Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterface
     */
    public function setDickerdatacategoryId($dickerdatacategoryId)
    {
        return $this->setData(self::XITCATEGORY_ID, $dickerdatacategoryId);
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
     * @return \Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterface
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Mageseller\DickerdataImport\Api\Data\DickerdataCategoryExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Mageseller\DickerdataImport\Api\Data\DickerdataCategoryExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Mageseller\DickerdataImport\Api\Data\DickerdataCategoryExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
