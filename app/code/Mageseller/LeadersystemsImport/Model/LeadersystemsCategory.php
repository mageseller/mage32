<?php
/**
 * A Magento 2 module named Mageseller/LeadersystemsImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/LeadersystemsImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\LeadersystemsImport\Model;

use Magento\Framework\Api\DataObjectHelper;
use Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface;
use Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterfaceFactory;

class LeadersystemsCategory extends \Magento\Framework\Model\AbstractModel
{
    protected $leadersystemscategoryDataFactory;

    protected $dataObjectHelper;

    protected $_eventPrefix = 'mageseller_leadersystemsimport_leadersystemscategory';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param LeadersystemsCategoryInterfaceFactory $leadersystemscategoryDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Mageseller\LeadersystemsImport\Model\ResourceModel\LeadersystemsCategory $resource
     * @param \Mageseller\LeadersystemsImport\Model\ResourceModel\LeadersystemsCategory\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        LeadersystemsCategoryInterfaceFactory $leadersystemscategoryDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Mageseller\LeadersystemsImport\Model\ResourceModel\LeadersystemsCategory $resource,
        \Mageseller\LeadersystemsImport\Model\ResourceModel\LeadersystemsCategory\Collection $resourceCollection,
        array $data = []
    ) {
        $this->leadersystemscategoryDataFactory = $leadersystemscategoryDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve leadersystemscategory model with leadersystemscategory data
     * @return LeadersystemsCategoryInterface
     */
    public function getDataModel()
    {
        $leadersystemscategoryData = $this->getData();

        $leadersystemscategoryDataObject = $this->leadersystemscategoryDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $leadersystemscategoryDataObject,
            $leadersystemscategoryData,
            LeadersystemsCategoryInterface::class
        );

        return $leadersystemscategoryDataObject;
    }
}
