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

namespace Mageseller\DickerdataImport\Model;

use Magento\Framework\Api\DataObjectHelper;
use Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterface;
use Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterfaceFactory;

class DickerdataCategory extends \Magento\Framework\Model\AbstractModel
{
    protected $dickerdatacategoryDataFactory;

    protected $dataObjectHelper;

    protected $_eventPrefix = 'mageseller_dickerdataimport_dickerdatacategory';

    /**
     * @param \Magento\Framework\Model\Context                                               $context
     * @param \Magento\Framework\Registry                                                    $registry
     * @param DickerdataCategoryInterfaceFactory                                             $dickerdatacategoryDataFactory
     * @param DataObjectHelper                                                               $dataObjectHelper
     * @param \Mageseller\DickerdataImport\Model\ResourceModel\DickerdataCategory            $resource
     * @param \Mageseller\DickerdataImport\Model\ResourceModel\DickerdataCategory\Collection $resourceCollection
     * @param array                                                                          $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        DickerdataCategoryInterfaceFactory $dickerdatacategoryDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Mageseller\DickerdataImport\Model\ResourceModel\DickerdataCategory $resource,
        \Mageseller\DickerdataImport\Model\ResourceModel\DickerdataCategory\Collection $resourceCollection,
        array $data = []
    ) {
        $this->dickerdatacategoryDataFactory = $dickerdatacategoryDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve dickerdatacategory model with dickerdatacategory data
     *
     * @return DickerdataCategoryInterface
     */
    public function getDataModel()
    {
        $dickerdatacategoryData = $this->getData();

        $dickerdatacategoryDataObject = $this->dickerdatacategoryDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $dickerdatacategoryDataObject,
            $dickerdatacategoryData,
            DickerdataCategoryInterface::class
        );

        return $dickerdatacategoryDataObject;
    }
}
