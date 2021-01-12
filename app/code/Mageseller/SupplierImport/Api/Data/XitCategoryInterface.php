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

namespace Mageseller\SupplierImport\Api\Data;

interface XitCategoryInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const NAME = 'name';
    const XITCATEGORY_ID = 'xitcategory_id';

    /**
     * Get xitcategory_id
     * @return string|null
     */
    public function getXitcategoryId();

    /**
     * Set xitcategory_id
     * @param string $xitcategoryId
     * @return \Mageseller\SupplierImport\Api\Data\XitCategoryInterface
     */
    public function setXitcategoryId($xitcategoryId);

    /**
     * Get name
     * @return string|null
     */
    public function getName();

    /**
     * Set name
     * @param string $name
     * @return \Mageseller\SupplierImport\Api\Data\XitCategoryInterface
     */
    public function setName($name);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Mageseller\SupplierImport\Api\Data\XitCategoryExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Mageseller\SupplierImport\Api\Data\XitCategoryExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Mageseller\SupplierImport\Api\Data\XitCategoryExtensionInterface $extensionAttributes
    );
}
