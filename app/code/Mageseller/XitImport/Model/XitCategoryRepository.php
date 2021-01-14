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

use Mageseller\XitImport\Api\XitCategoryRepositoryInterface;
use Mageseller\XitImport\Api\Data\XitCategorySearchResultsInterfaceFactory;
use Mageseller\XitImport\Api\Data\XitCategoryInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Mageseller\XitImport\Model\ResourceModel\XitCategory as ResourceXitCategory;
use Mageseller\XitImport\Model\ResourceModel\XitCategory\CollectionFactory as XitCategoryCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\ExtensibleDataObjectConverter;

class XitCategoryRepository implements XitCategoryRepositoryInterface
{

    protected $resource;

    protected $xitCategoryFactory;

    protected $xitCategoryCollectionFactory;

    protected $searchResultsFactory;

    protected $dataObjectHelper;

    protected $dataObjectProcessor;

    protected $dataXitCategoryFactory;

    protected $extensionAttributesJoinProcessor;

    private $storeManager;

    private $collectionProcessor;

    protected $extensibleDataObjectConverter;

    /**
     * @param ResourceXitCategory $resource
     * @param XitCategoryFactory $xitCategoryFactory
     * @param XitCategoryInterfaceFactory $dataXitCategoryFactory
     * @param XitCategoryCollectionFactory $xitCategoryCollectionFactory
     * @param XitCategorySearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceXitCategory $resource,
        XitCategoryFactory $xitCategoryFactory,
        XitCategoryInterfaceFactory $dataXitCategoryFactory,
        XitCategoryCollectionFactory $xitCategoryCollectionFactory,
        XitCategorySearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->xitCategoryFactory = $xitCategoryFactory;
        $this->xitCategoryCollectionFactory = $xitCategoryCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataXitCategoryFactory = $dataXitCategoryFactory;
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
        \Mageseller\XitImport\Api\Data\XitCategoryInterface $xitCategory
    ) {
        /* if (empty($xitCategory->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $xitCategory->setStoreId($storeId);
        } */
        
        $xitCategoryData = $this->extensibleDataObjectConverter->toNestedArray(
            $xitCategory,
            [],
            \Mageseller\XitImport\Api\Data\XitCategoryInterface::class
        );
        
        $xitCategoryModel = $this->xitCategoryFactory->create()->setData($xitCategoryData);
        
        try {
            $this->resource->save($xitCategoryModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the xitCategory: %1',
                $exception->getMessage()
            ));
        }
        return $xitCategoryModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getById($xitCategoryId)
    {
        $xitCategory = $this->xitCategoryFactory->create();
        $this->resource->load($xitCategory, $xitCategoryId);
        if (!$xitCategory->getId()) {
            throw new NoSuchEntityException(__('XitCategory with id "%1" does not exist.', $xitCategoryId));
        }
        return $xitCategory->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->xitCategoryCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Mageseller\XitImport\Api\Data\XitCategoryInterface::class
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
        \Mageseller\XitImport\Api\Data\XitCategoryInterface $xitCategory
    ) {
        try {
            $xitCategoryModel = $this->xitCategoryFactory->create();
            $this->resource->load($xitCategoryModel, $xitCategory->getXitcategoryId());
            $this->resource->delete($xitCategoryModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the XitCategory: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($xitCategoryId)
    {
        return $this->delete($this->getById($xitCategoryId));
    }
}
