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

use Mageseller\LeadersystemsImport\Api\LeadersystemsCategoryRepositoryInterface;
use Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategorySearchResultsInterfaceFactory;
use Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Mageseller\LeadersystemsImport\Model\ResourceModel\LeadersystemsCategory as ResourceLeadersystemsCategory;
use Mageseller\LeadersystemsImport\Model\ResourceModel\LeadersystemsCategory\CollectionFactory as LeadersystemsCategoryCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\ExtensibleDataObjectConverter;

class LeadersystemsCategoryRepository implements LeadersystemsCategoryRepositoryInterface
{

    protected $resource;

    protected $leadersystemsCategoryFactory;

    protected $leadersystemsCategoryCollectionFactory;

    protected $searchResultsFactory;

    protected $dataObjectHelper;

    protected $dataObjectProcessor;

    protected $dataLeadersystemsCategoryFactory;

    protected $extensionAttributesJoinProcessor;

    private $storeManager;

    private $collectionProcessor;

    protected $extensibleDataObjectConverter;

    /**
     * @param ResourceLeadersystemsCategory                      $resource
     * @param LeadersystemsCategoryFactory                       $leadersystemsCategoryFactory
     * @param LeadersystemsCategoryInterfaceFactory              $dataLeadersystemsCategoryFactory
     * @param LeadersystemsCategoryCollectionFactory             $leadersystemsCategoryCollectionFactory
     * @param LeadersystemsCategorySearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper                                   $dataObjectHelper
     * @param DataObjectProcessor                                $dataObjectProcessor
     * @param StoreManagerInterface                              $storeManager
     * @param CollectionProcessorInterface                       $collectionProcessor
     * @param JoinProcessorInterface                             $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter                      $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceLeadersystemsCategory $resource,
        LeadersystemsCategoryFactory $leadersystemsCategoryFactory,
        LeadersystemsCategoryInterfaceFactory $dataLeadersystemsCategoryFactory,
        LeadersystemsCategoryCollectionFactory $leadersystemsCategoryCollectionFactory,
        LeadersystemsCategorySearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->leadersystemsCategoryFactory = $leadersystemsCategoryFactory;
        $this->leadersystemsCategoryCollectionFactory = $leadersystemsCategoryCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataLeadersystemsCategoryFactory = $dataLeadersystemsCategoryFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface $leadersystemsCategory
    ) {
        /* if (empty($leadersystemsCategory->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $leadersystemsCategory->setStoreId($storeId);
        } */
        
        $leadersystemsCategoryData = $this->extensibleDataObjectConverter->toNestedArray(
            $leadersystemsCategory,
            [],
            \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface::class
        );
        
        $leadersystemsCategoryModel = $this->leadersystemsCategoryFactory->create()->setData($leadersystemsCategoryData);
        
        try {
            $this->resource->save($leadersystemsCategoryModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the leadersystemsCategory: %1',
                    $exception->getMessage()
                )
            );
        }
        return $leadersystemsCategoryModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getById($leadersystemsCategoryId)
    {
        $leadersystemsCategory = $this->leadersystemsCategoryFactory->create();
        $this->resource->load($leadersystemsCategory, $leadersystemsCategoryId);
        if (!$leadersystemsCategory->getId()) {
            throw new NoSuchEntityException(__('LeadersystemsCategory with id "%1" does not exist.', $leadersystemsCategoryId));
        }
        return $leadersystemsCategory->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->leadersystemsCategoryCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface::class
        );
        
        $this->collectionProcessor->process($criteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \Mageseller\LeadersystemsImport\Api\Data\LeadersystemsCategoryInterface $leadersystemsCategory
    ) {
        try {
            $leadersystemsCategoryModel = $this->leadersystemsCategoryFactory->create();
            $this->resource->load($leadersystemsCategoryModel, $leadersystemsCategory->getLeadersystemscategoryId());
            $this->resource->delete($leadersystemsCategoryModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __(
                    'Could not delete the LeadersystemsCategory: %1',
                    $exception->getMessage()
                )
            );
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($leadersystemsCategoryId)
    {
        return $this->delete($this->getById($leadersystemsCategoryId));
    }
}
