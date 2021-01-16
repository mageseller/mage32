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

namespace Mageseller\XitImport\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Mageseller\Process\Helper\Product\Import\Url;
use Mageseller\Process\Model\Process;
use Mageseller\Process\Model\Product\Import\Indexer\Indexer;
use Mageseller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Mageseller\XitImport\Helper\Product\Import\Inventory as InventoryHelper;

class ProductHelper extends AbstractHelper
{
    const FILENAME = 'vendor-file.xml';
    const TMP_FILENAME = 'vendor-file-tmp.xml';
    const DOWNLOAD_FOLDER = 'supplier/xit';
    const XIT_IMPORTCONFIG_IS_ENABLE = 'xit/importconfig/is_enable';
    const ATTRIBUTE_PRODUCT_SKU = 'sku';
    /**
     * /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_mediaDirectory;
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $_storeManager;
    /**
     *
     * @var unknown
     */
    protected $_dirReader;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     *
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $fileFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;
    /**
     * @var \Mageseller\XitImport\Logger\XitImport
     */
    protected $xitimportLogger;
    private $apiUrl;
    /**
     * @var \Mageseller\XitImport\Model\XitCategoryFactory
     */
    private $xitCategoryFactory;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $categoryFactory;
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;
    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Indexer
     */
    protected $indexer;
    /**
     * @var ProcessResourceFactory
     */
    protected $processResourceFactory;
    private $supplierCategories;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var ProductResourceFactory
     */
    protected $productResourceFactory;
    /**
     * @var InventoryHelper
     */
    protected $inventoryHelper;
    /**
     * @var StoreInterface[]
     */
    private $stores;
    protected $xitCategoryIdsWithName;
    private $websiteIds;
    private $urlHelper;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Magento\Framework\Filesystem\DirectoryList $dirReader
     * @param \Magento\Framework\Filesystem\Io\File $fileFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param MessageManagerInterface $messageManager
     * @param \Mageseller\XitImport\Logger\XitImport $xitimportLogger
     * @param \Mageseller\XitImport\Model\XitCategoryFactory $xitCategoryFactory
     * @param CollectionFactory $categoryCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param Indexer $indexer
     * @param ProcessResourceFactory $processResourceFactory
     * @param ResourceConnection $resource
     * @param EavConfig $eavConfig
     * @param ProductFactory $productFactory
     * @param ProductResourceFactory $productResourceFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param InventoryHelper $inventory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\Filesystem\DirectoryList $dirReader,
        \Magento\Framework\Filesystem\Io\File $fileFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        MessageManagerInterface $messageManager,
        \Mageseller\XitImport\Logger\XitImport $xitimportLogger,
        \Mageseller\XitImport\Model\XitCategoryFactory $xitCategoryFactory,
        CollectionFactory $categoryCollectionFactory,
        ResourceConnection $resourceConnection,
        Indexer $indexer,
        ProcessResourceFactory $processResourceFactory,
        ResourceConnection $resource,
        EavConfig $eavConfig,
        ProductFactory $productFactory,
        ProductResourceFactory $productResourceFactory,
        ProductCollectionFactory $productCollectionFactory,
        InventoryHelper $inventoryHelper,
        \Mageseller\Process\Helper\Product\Import\Url $urlHelper,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory
    ) {
        parent::__construct($context);
        $this->_dateTime = $dateTime;
        $this->fileFactory = $fileFactory;
        $this->scopeConfig = $context->getScopeConfig();
        $this->_dirReader = $dirReader;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->_storeManager = $storeManager;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->xitimportLogger = $xitimportLogger;
        $this->xitCategoryFactory = $xitCategoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->resourceConnection = $resourceConnection;
        $this->indexer                = $indexer;
        $this->processResourceFactory = $processResourceFactory;
        $this->resource                 = $resource;
        $this->connection               = $resource->getConnection();
        $this->eavConfig                = $eavConfig;
        $this->productFactory           = $productFactory;
        $this->productResourceFactory   = $productResourceFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->inventoryHelper = $inventoryHelper;
        $this->urlHelper = $urlHelper;
    }

    public function processProducts($items, Process $process, $since, $sendReport = true)
    {
        try {
            // Disable or not the indexing when UpdateOnSave mode
            $this->indexer->initIndexers();
            $processResource = $this->processResourceFactory->create();
            $processResource->save($process);
            $i = 0; // Line number
            foreach ($items as $item) {
                $sku =  (string) $item->ItemDetail->ManufacturerPartID;
                try {
                    ++$i;
                    $start = microtime(true);

                    $this->import($item);

                    $time = round(microtime(true) - $start, 2);
                    $message = __("[OK] Product has been saved for sku %1 ( $time s)", $sku);
                    $process->output($message);
                    if ($i % 5 === 0) {
                        $processResource->save($process);
                    }
                } catch (WarningException $e) {
                    $message = __("Warning on sku %1: {$e->getMessage()}", $sku);
                    $process->output($message);
                } catch (\Exception $e) {
                    $error = __("Error for sku %1: {$e->getMessage()}", $sku);

                    $process->output($error);
                    echo $e->getTraceAsString();
                    die;
                }
            }
        } catch (\Exception $e) {
            $process->fail($e->getMessage());
            throw $e;
        } finally {
            // Reindex
            $process->output(__('Reindexing...'), true);
            $this->indexer->reindex();
        }

        $process->output(__('Done!'));
    }

    public function parseObject($value)
    {
        return isset($value) ? is_object($value) ? array_filter(json_decode(json_encode($value), true), function ($value) {
            return !is_array($value) && $value !== '';
        }) : $value : [];
    }

    public function parseValue($value)
    {
        return isset($value) ? trim($value) : "";
    }

    private function import($data)
    {
        $sku = (string) $data->ItemDetail->ManufacturerPartID;
        $product = $this->findProductBySku($sku);
        $productExists = (bool) $product;
        if (!$productExists) {
            $product = $this->createSimpleProduct($data);
            $this->saveProduct($product);
            $this->indexer->setIdsToIndex($product);
        }
    }
    public function findProductBySku($productSku)
    {
        $product = null;

        if (!empty($miraklProductSku)) {
            $product = $this->findProductByAttribute(
                self::ATTRIBUTE_PRODUCT_SKU,
                $productSku,
                Product\Type::TYPE_SIMPLE
            );
        }

        return $product;
    }
    public function findProductByAttribute($attrCode, $value, $type = null)
    {
        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter($attrCode, $value);

        if ($type) {
            $collection->addFieldToFilter('type_id', $type);
        }

        $collection->setPage(1, 1); // Limit to 1 result

        if ($collection->count()) {
            return $collection->getFirstItem()->setStoreId(0);
        }

        return null;
    }

    private function createSimpleProduct($data)
    {
        $product = $this->createProduct(
            Product\Type::TYPE_SIMPLE,
            $data,
            ['use_config_manage_stock' => 1, 'is_in_stock' => 0, 'qty' => 0, 'type_id' => 'simple']
        );

        $this->inventoryHelper->createSourceItems($product);
        return $product;
    }
    /**
     * Creates and initializes a product according to arguments
     *
     * @param   string          $type
     * @param   $categoryCollection
     * @param   $data
     * @param   array           $stockData
     * @return  ProductModel
     */
    private function createProduct($type, &$data, array $stockData = [])
    {
        $sku =  (string) $data->ItemDetail->ManufacturerPartID;
        $name = (string) $data->ItemDetail->Title;
        $price =  (string) $data->ItemDetail->UnitPrice;
        $taxRate = (string) $data->ItemDetail->TaxRate;
        $updatedAt = (string) $data->UpdatedAt;
        $taxClassId = 2;
        $attributeSetId = 4;
        $categoryIds = $this->validateCategory($data);
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productFactory->create();
        $product->setSku($sku)
            ->setName($name)
            ->setTypeId($type)
            ->setStatus(Status::STATUS_ENABLED)
            ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
            ->setAttributeSetId($attributeSetId)
            ->setPrice($price)
            ->setStoreId(0)
            ->setWebsiteIds($this->getWebsiteIds())
            ->setTaxClassId($taxClassId);

        if ($categoryIds) {
            $product->setCategoryIds($categoryIds);
        }

        $product->setUrlKey($this->getProductUrlKey($product));

        $product->setStockData($stockData);

        return $product;
    }
    /**
     * Saves specified product with duplicate URL key error handling
     *
     * @param   Product $product
     */
    protected function saveProduct(Product $product)
    {
        $this->urlHelper->generateUrlKey($product);

        /** @var \Magento\Catalog\Model\ResourceModel\Product $productResource */
        $productResource = $this->productResourceFactory->create();

        try {
            $productResource->save($product);
        } catch (\Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException $e) {
            // If URL rewrite is already used, make it unique and generate again
            $urlKey = $product->getUrlKey() . '-' . $product->getId();
            $product->setUrlKey($urlKey);
            $product->setUrlPath($urlKey);
            $productResource->save($product);
        }
    }
    private function validateCategory(&$data)
    {
        $categories = $this->parseObject($data->ItemDetail->Classifications->Classification);
        unset($categories['@attributes']);

        if ($this->xitCategoryIdsWithName == null) {
            $xitCategoryCollection = $this->xitCategoryFactory->create()->getCollection();
            $select = $xitCategoryCollection->getSelect()->reset(Select::COLUMNS)->columns(['name','id' => 'xitcategory_id']);
            $this->xitCategoryIdsWithName = $this->connection->fetchAssoc($select);
        }
        $xitCategoryIds = [];
        foreach ($categories as $category) {
            $xitCategoryIds = $this->xitCategoryIdsWithName[$category]['id'] ?? 0;
        }

        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->addAttributeToFilter('xit_category_ids', ['in' => $xitCategoryIds]);
        $allIds = array_unique($categoryCollection->getAllIds());
        return $allIds;
    }
    /**
     * Returns website ids to enable for product import
     *
     * @return  array
     */
    private function getWebsiteIds()
    {
        if ($this->websiteIds == null) {
            if ($this->_storeManager->isSingleStoreMode()) {
                $this->websiteIds[] = $this->_storeManager->getDefaultStoreView()->getWebsiteId();
            } else {
                foreach ($this->getStores() as $store) {
                    if ($websiteId = $store->getWebsiteId()) {
                        $this->websiteIds[] = $websiteId;
                    }
                }
            }
            $this->websiteIds = array_unique($this->websiteIds);
        }
        return $this->websiteIds;
    }
    /**
     * Returns stores used for product import
     *
     * @return  StoreInterface[]
     */
    public function getStores()
    {
        if (null === $this->stores) {
            $this->stores = [];
            foreach ($this->_storeManager->getStores(true) as $store) {
                $this->stores[] = $store;
            }
        }

        return $this->stores;
    }
    public function getProductUrlKey(Product $product)
    {
        $urlKey = $product->formatUrlKey($product->getName());
        if (empty($urlKey) || is_numeric($urlKey)) {
            $urlKey = $product->getSku();
        }

        return $urlKey;
    }
}
