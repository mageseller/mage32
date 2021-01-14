<?php
/**
 * A Magento 2 module named Mageseller/XitImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/XitImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\XitImport\Model;

use Magento\Framework\Api\DataObjectHelper;
use Mageseller\XitImport\Api\Data\XitCategoryInterface;
use Mageseller\XitImport\Api\Data\XitCategoryInterfaceFactory;

class XitCategory extends \Magento\Framework\Model\AbstractModel
{
    protected $xitcategoryDataFactory;

    protected $dataObjectHelper;

    protected $_eventPrefix = 'mageseller_xitimport_xitcategory';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param XitCategoryInterfaceFactory $xitcategoryDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Mageseller\XitImport\Model\ResourceModel\XitCategory $resource
     * @param \Mageseller\XitImport\Model\ResourceModel\XitCategory\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        XitCategoryInterfaceFactory $xitcategoryDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Mageseller\XitImport\Model\ResourceModel\XitCategory $resource,
        \Mageseller\XitImport\Model\ResourceModel\XitCategory\Collection $resourceCollection,
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
