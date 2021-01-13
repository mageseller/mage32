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

namespace Mageseller\SupplierImport\Model\Category\Attribute\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Mageseller\SupplierImport\Helper\Xit;

class XitCategoryIds extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource implements OptionSourceInterface
{
    /**
     * @var Xit
     */
    private $helper;

    public function __construct(Xit $helper)
    {
        $this->helper = $helper;
    }
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }
    /**
     * getAllOptions
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $supplierData = $this->helper->getSupplierCatData();
            foreach ($supplierData as $supplierId => $supplier) {
                $this->_options[] = ['value' => $supplierId , 'label' => __($supplier['name'])];
            }
        }
        return $this->_options;
    }
}
