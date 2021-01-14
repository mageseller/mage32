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

namespace Mageseller\IngrammicroImport\Model\Category\Attribute\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Mageseller\IngrammicroImport\Helper\Ingrammicro;

class IngrammicroCategoryIds extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource implements OptionSourceInterface
{
    /**
     * @var Ingrammicro
     */
    private $helper;

    public function __construct(Ingrammicro $helper)
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
            $this->_options = [];
            $supplierData = $this->helper->getSupplierCatData();
            foreach ($supplierData as $supplierId => $supplier) {
                $this->_options[] = ['value' => $supplierId , 'label' => __($supplier['name'])];
            }
        }
        return $this->_options;
    }
}
