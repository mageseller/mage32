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

namespace Mageseller\IngrammicroImport\Model;

use Magento\Framework\Api\DataObjectHelper;
use Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface;
use Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterfaceFactory;

class IngrammicroCategory extends \Magento\Framework\Model\AbstractModel
{
    protected $ingrammicrocategoryDataFactory;

    protected $dataObjectHelper;

    protected $_eventPrefix = 'mageseller_ingrammicroimport_ingrammicrocategory';

    /**
     * @param \Magento\Framework\Model\Context                                                 $context
     * @param \Magento\Framework\Registry                                                      $registry
     * @param IngrammicroCategoryInterfaceFactory                                              $ingrammicrocategoryDataFactory
     * @param DataObjectHelper                                                                 $dataObjectHelper
     * @param \Mageseller\IngrammicroImport\Model\ResourceModel\IngrammicroCategory            $resource
     * @param \Mageseller\IngrammicroImport\Model\ResourceModel\IngrammicroCategory\Collection $resourceCollection
     * @param array                                                                            $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        IngrammicroCategoryInterfaceFactory $ingrammicrocategoryDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Mageseller\IngrammicroImport\Model\ResourceModel\IngrammicroCategory $resource,
        \Mageseller\IngrammicroImport\Model\ResourceModel\IngrammicroCategory\Collection $resourceCollection,
        array $data = []
    ) {
        $this->ingrammicrocategoryDataFactory = $ingrammicrocategoryDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve ingrammicrocategory model with ingrammicrocategory data
     *
     * @return IngrammicroCategoryInterface
     */
    public function getDataModel()
    {
        $ingrammicrocategoryData = $this->getData();

        $ingrammicrocategoryDataObject = $this->ingrammicrocategoryDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $ingrammicrocategoryDataObject,
            $ingrammicrocategoryData,
            IngrammicroCategoryInterface::class
        );

        return $ingrammicrocategoryDataObject;
    }
}
