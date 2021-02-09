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

use Mageseller\DickerdataImport\Api\DickerdataCategoryRepositoryInterface;
use Mageseller\DickerdataImport\Api\Data\DickerdataCategorySearchResultsInterfaceFactory;
use Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Mageseller\DickerdataImport\Model\ResourceModel\DickerdataCategory as ResourceDickerdataCategory;
use Mageseller\DickerdataImport\Model\ResourceModel\DickerdataCategory\CollectionFactory as DickerdataCategoryCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\ExtensibleDataObjectConverter;

class DickerdataCategoryRepository implements DickerdataCategoryRepositoryInterface
{

    protected $resource;

    protected $dickerdataCategoryFactory;

    protected $dickerdataCategoryCollectionFactory;

    protected $searchResultsFactory;

    protected $dataObjectHelper;

    protected $dataObjectProcessor;

    protected $dataDickerdataCategoryFactory;

    protected $extensionAttributesJoinProcessor;

    private $storeManager;

    private $collectionProcessor;

    protected $extensibleDataObjectConverter;

    /**
     * @param ResourceDickerdataCategory                      $resource
     * @param DickerdataCategoryFactory                       $dickerdataCategoryFactory
     * @param DickerdataCategoryInterfaceFactory              $dataDickerdataCategoryFactory
     * @param DickerdataCategoryCollectionFactory             $dickerdataCategoryCollectionFactory
     * @param DickerdataCategorySearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper                                $dataObjectHelper
     * @param DataObjectProcessor                             $dataObjectProcessor
     * @param StoreManagerInterface                           $storeManager
     * @param CollectionProcessorInterface                    $collectionProcessor
     * @param JoinProcessorInterface                          $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter                   $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceDickerdataCategory $resource,
        DickerdataCategoryFactory $dickerdataCategoryFactory,
        DickerdataCategoryInterfaceFactory $dataDickerdataCategoryFactory,
        DickerdataCategoryCollectionFactory $dickerdataCategoryCollectionFactory,
        DickerdataCategorySearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->dickerdataCategoryFactory = $dickerdataCategoryFactory;
        $this->dickerdataCategoryCollectionFactory = $dickerdataCategoryCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataDickerdataCategoryFactory = $dataDickerdataCategoryFactory;
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
        \Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterface $dickerdataCategory
    ) {
        /* if (empty($dickerdataCategory->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $dickerdataCategory->setStoreId($storeId);
        } */
        
        $dickerdataCategoryData = $this->extensibleDataObjectConverter->toNestedArray(
            $dickerdataCategory,
            [],
            \Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterface::class
        );
        
        $dickerdataCategoryModel = $this->dickerdataCategoryFactory->create()->setData($dickerdataCategoryData);
        
        try {
            $this->resource->save($dickerdataCategoryModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the dickerdataCategory: %1',
                    $exception->getMessage()
                )
            );
        }
        return $dickerdataCategoryModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getById($dickerdataCategoryId)
    {
        $dickerdataCategory = $this->dickerdataCategoryFactory->create();
        $this->resource->load($dickerdataCategory, $dickerdataCategoryId);
        if (!$dickerdataCategory->getId()) {
            throw new NoSuchEntityException(__('DickerdataCategory with id "%1" does not exist.', $dickerdataCategoryId));
        }
        return $dickerdataCategory->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->dickerdataCategoryCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterface::class
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
        \Mageseller\DickerdataImport\Api\Data\DickerdataCategoryInterface $dickerdataCategory
    ) {
        try {
            $dickerdataCategoryModel = $this->dickerdataCategoryFactory->create();
            $this->resource->load($dickerdataCategoryModel, $dickerdataCategory->getDickerdatacategoryId());
            $this->resource->delete($dickerdataCategoryModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __(
                    'Could not delete the DickerdataCategory: %1',
                    $exception->getMessage()
                )
            );
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($dickerdataCategoryId)
    {
        return $this->delete($this->getById($dickerdataCategoryId));
    }
}
