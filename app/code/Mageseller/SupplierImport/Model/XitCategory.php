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

namespace Mageseller\SupplierImport\Model;

use Mageseller\SupplierImport\Api\Data\XitCategoryInterface;
use Mageseller\SupplierImport\Api\Data\XitCategoryInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;

class XitCategory extends \Magento\Framework\Model\AbstractModel
{

    protected $xitcategoryDataFactory;

    protected $dataObjectHelper;

    protected $_eventPrefix = 'mageseller_supplierimport_xitcategory';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param XitCategoryInterfaceFactory $xitcategoryDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Mageseller\SupplierImport\Model\ResourceModel\XitCategory $resource
     * @param \Mageseller\SupplierImport\Model\ResourceModel\XitCategory\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        XitCategoryInterfaceFactory $xitcategoryDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Mageseller\SupplierImport\Model\ResourceModel\XitCategory $resource,
        \Mageseller\SupplierImport\Model\ResourceModel\XitCategory\Collection $resourceCollection,
        array $data = []
    ) {
        $this->xitcategoryDataFactory = $xitcategoryDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve xitcategory model with xitcategory data
     * @return XitCategoryInterface
     */
    public function getDataModel()
    {
        $xitcategoryData = $this->getData();
        
        $xitcategoryDataObject = $this->xitcategoryDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $xitcategoryDataObject,
            $xitcategoryData,
            XitCategoryInterface::class
        );
        
        return $xitcategoryDataObject;
    }
}
