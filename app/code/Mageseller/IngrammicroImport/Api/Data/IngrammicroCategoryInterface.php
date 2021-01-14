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

namespace Mageseller\IngrammicroImport\Api\Data;

interface IngrammicroCategoryInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const NAME = 'name';
    const XITCATEGORY_ID = 'ingrammicrocategory_id';

    /**
     * Get ingrammicrocategory_id
     * @return string|null
     */
    public function getIngrammicrocategoryId();

    /**
     * Set ingrammicrocategory_id
     * @param string $ingrammicrocategoryId
     * @return \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface
     */
    public function setIngrammicrocategoryId($ingrammicrocategoryId);

    /**
     * Get name
     * @return string|null
     */
    public function getName();

    /**
     * Set name
     * @param string $name
     * @return \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface
     */
    public function setName($name);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryExtensionInterface $extensionAttributes
    );
}
