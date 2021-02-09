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

namespace Mageseller\IngrammicroImport\Model\Data;

use Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface;

class IngrammicroCategory extends \Magento\Framework\Api\AbstractExtensibleObject implements IngrammicroCategoryInterface
{

    /**
     * Get ingrammicrocategory_id
     *
     * @return string|null
     */
    public function getIngrammicrocategoryId()
    {
        return $this->_get(self::INGRAMMICROCATEGORY_ID);
    }

    /**
     * Set ingrammicrocategory_id
     *
     * @param  string $ingrammicrocategoryId
     * @return \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface
     */
    public function setIngrammicrocategoryId($ingrammicrocategoryId)
    {
        return $this->setData(self::INGRAMMICROCATEGORY_ID, $ingrammicrocategoryId);
    }

    /**
     * Get name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->_get(self::NAME);
    }

    /**
     * Set name
     *
     * @param  string $name
     * @return \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     *
     * @param  \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
