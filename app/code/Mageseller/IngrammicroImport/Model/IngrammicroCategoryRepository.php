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

use Mageseller\IngrammicroImport\Api\IngrammicroCategoryRepositoryInterface;
use Mageseller\IngrammicroImport\Api\Data\IngrammicroCategorySearchResultsInterfaceFactory;
use Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Mageseller\IngrammicroImport\Model\ResourceModel\IngrammicroCategory as ResourceIngrammicroCategory;
use Mageseller\IngrammicroImport\Model\ResourceModel\IngrammicroCategory\CollectionFactory as IngrammicroCategoryCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\ExtensibleDataObjectConverter;

class IngrammicroCategoryRepository implements IngrammicroCategoryRepositoryInterface
{

    protected $resource;

    protected $ingrammicroCategoryFactory;

    protected $ingrammicroCategoryCollectionFactory;

    protected $searchResultsFactory;

    protected $dataObjectHelper;

    protected $dataObjectProcessor;

    protected $dataIngrammicroCategoryFactory;

    protected $extensionAttributesJoinProcessor;

    private $storeManager;

    private $collectionProcessor;

    protected $extensibleDataObjectConverter;

    /**
     * @param ResourceIngrammicroCategory                      $resource
     * @param IngrammicroCategoryFactory                       $ingrammicroCategoryFactory
     * @param IngrammicroCategoryInterfaceFactory              $dataIngrammicroCategoryFactory
     * @param IngrammicroCategoryCollectionFactory             $ingrammicroCategoryCollectionFactory
     * @param IngrammicroCategorySearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper                                 $dataObjectHelper
     * @param DataObjectProcessor                              $dataObjectProcessor
     * @param StoreManagerInterface                            $storeManager
     * @param CollectionProcessorInterface                     $collectionProcessor
     * @param JoinProcessorInterface                           $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter                    $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceIngrammicroCategory $resource,
        IngrammicroCategoryFactory $ingrammicroCategoryFactory,
        IngrammicroCategoryInterfaceFactory $dataIngrammicroCategoryFactory,
        IngrammicroCategoryCollectionFactory $ingrammicroCategoryCollectionFactory,
        IngrammicroCategorySearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->ingrammicroCategoryFactory = $ingrammicroCategoryFactory;
        $this->ingrammicroCategoryCollectionFactory = $ingrammicroCategoryCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataIngrammicroCategoryFactory = $dataIngrammicroCategoryFactory;
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
        \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface $ingrammicroCategory
    ) {
        /* if (empty($ingrammicroCategory->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $ingrammicroCategory->setStoreId($storeId);
        } */
        
        $ingrammicroCategoryData = $this->extensibleDataObjectConverter->toNestedArray(
            $ingrammicroCategory,
            [],
            \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface::class
        );
        
        $ingrammicroCategoryModel = $this->ingrammicroCategoryFactory->create()->setData($ingrammicroCategoryData);
        
        try {
            $this->resource->save($ingrammicroCategoryModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the ingrammicroCategory: %1',
                    $exception->getMessage()
                )
            );
        }
        return $ingrammicroCategoryModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getById($ingrammicroCategoryId)
    {
        $ingrammicroCategory = $this->ingrammicroCategoryFactory->create();
        $this->resource->load($ingrammicroCategory, $ingrammicroCategoryId);
        if (!$ingrammicroCategory->getId()) {
            throw new NoSuchEntityException(__('IngrammicroCategory with id "%1" does not exist.', $ingrammicroCategoryId));
        }
        return $ingrammicroCategory->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->ingrammicroCategoryCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface::class
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
        \Mageseller\IngrammicroImport\Api\Data\IngrammicroCategoryInterface $ingrammicroCategory
    ) {
        try {
            $ingrammicroCategoryModel = $this->ingrammicroCategoryFactory->create();
            $this->resource->load($ingrammicroCategoryModel, $ingrammicroCategory->getIngrammicrocategoryId());
            $this->resource->delete($ingrammicroCategoryModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __(
                    'Could not delete the IngrammicroCategory: %1',
                    $exception->getMessage()
                )
            );
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($ingrammicroCategoryId)
    {
        return $this->delete($this->getById($ingrammicroCategoryId));
    }
}
