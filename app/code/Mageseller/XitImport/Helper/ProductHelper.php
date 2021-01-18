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
use Magento\InventoryCache\Model\FlushCacheByProductIds;
use Mageseller\Process\Helper\Product\Import\Url;
use Mageseller\Process\Model\Process;
use Mageseller\Process\Model\Product\Import\Indexer\Indexer;
use Mageseller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Mageseller\ProductImport\Api\Data\ProductStoreView;
use Mageseller\ProductImport\Api\Data\SimpleProduct;
use Mageseller\ProductImport\Api\ImportConfig;
use Mageseller\ProductImport\Model\Persistence\Magento2DbConnection;
use Mageseller\ProductImport\Model\Resource\MetaData;
use Mageseller\XitImport\Helper\Product\Import\Inventory as InventoryHelper;

class ProductHelper extends AbstractHelper
{
    const FILENAME = 'vendor-file.xml';
    const TMP_FILENAME = 'vendor-file-tmp.xml';
    const DOWNLOAD_FOLDER = 'supplier/xit';
    const XIT_IMPORTCONFIG_IS_ENABLE = 'xit/importconfig/is_enable';
    const ATTRIBUTE_PRODUCT_SKU = 'sku';
    const XIT_CATEGORY_TABLE = "mageseller_xitimport_xitcategory";
    const PDF_FOLDER = 'devicesPdf';
    const SUPPLIER = 'xitdistribution';
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
    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;
    /**
     * @var Indexer
     */
    protected $indexer;
    /**
     * @var ProcessResourceFactory
     */
    protected $processResourceFactory;
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
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $xitCategoryIdsWithName;
    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;
    /**
     * @var \Magento\CatalogInventory\Model\Indexer\Stock\Processor
     */
    protected $stockIndexerProcessor;
    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Price\Processor
     */
    protected $priceIndexer;
    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Eav\Processor
     */
    protected $_productEavIndexerProcessor;
    /**
     * @var FlushCacheByProductIds
     */
    private $flushCacheByProductIds;

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
     * @var ResourceConnection
     */
    private $resourceConnection;
    private $supplierCategories;
    /**
     * @var StoreInterface[]
     */
    private $stores;
    private $websiteIds;
    private $urlHelper;
    /**
     * @var \Mageseller\ProductImport\Api\ImporterFactory
     */
    private $importerFactory;
    /**  @var Magento2DbConnection */
    protected $db;
    /** @var MetaData */
    protected $metaData;
    private $existingXitCategoryIds;
    private $existingSkus;
    private $mediaUrl;

    /**
     * @var float|string
     */
    private $start;

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
     * @param InventoryHelper $inventoryHelper
     * @param Url $urlHelper
     * @param \Mageseller\ProductImport\Api\ImporterFactory $importerFactory
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param \Magento\CatalogInventory\Model\Indexer\Stock\Processor $stockIndexerProcessor
     * @param \Magento\Catalog\Model\Indexer\Product\Price\Processor $priceIndexer
     * @param \Magento\Catalog\Model\Indexer\Product\Eav\Processor $productEavIndexerProcessor
     * @param Magento2DbConnection $db
     * @param MetaData $metaData
     * @param FlushCacheByProductIds $flushCacheByProductIds
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
        \Mageseller\ProductImport\Api\ImporterFactory $importerFactory,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\CatalogInventory\Model\Indexer\Stock\Processor $stockIndexerProcessor,
        \Magento\Catalog\Model\Indexer\Product\Price\Processor $priceIndexer,
        \Magento\Catalog\Model\Indexer\Product\Eav\Processor $productEavIndexerProcessor,
        Magento2DbConnection $db,
        MetaData $metaData,
        FlushCacheByProductIds $flushCacheByProductIds,
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
        $this->indexer = $indexer;
        $this->processResourceFactory = $processResourceFactory;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->eavConfig = $eavConfig;
        $this->productFactory = $productFactory;
        $this->productResourceFactory = $productResourceFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->inventoryHelper = $inventoryHelper;
        $this->urlHelper = $urlHelper;
        $this->importerFactory = $importerFactory;
        $this->indexerRegistry = $indexerRegistry;
        $this->stockIndexerProcessor = $stockIndexerProcessor;
        $this->priceIndexer = $priceIndexer;
        $this->_productEavIndexerProcessor = $productEavIndexerProcessor;
        $this->flushCacheByProductIds = $flushCacheByProductIds;
        $this->db = $db;
        $this->metaData = $metaData;
        $this->mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function processProducts($items, Process $process, $since, $sendReport = true)
    {
        try {
            $allSkus = [];
            $allCategoryNames = [];
            foreach ($items as $item) {
                $allSkus[] = (string)$item->ItemDetail->ManufacturerPartID;
                $categories = $this->parseObject($item->ItemDetail->Classifications->Classification);
                unset($categories['@attributes']);
                //$categories = [];
                $allCategoryNames = array_unique(array_merge($allCategoryNames, $categories));
            }
            $this->existingXitCategoryIds = $this->getExistingXitCategoryIds($allCategoryNames);
            $this->existingSkus = $this->getExistingSkus($allSkus);
            // Disable or not the indexing when UpdateOnSave mode
            $this->indexer->initIndexers();
            $config = new ImportConfig();
            $config->duplicateUrlKeyStrategy = ImportConfig::DUPLICATE_KEY_STRATEGY_ADD_SERIAL;
            $productIdsToReindex = [];
            // a callback function to postprocess imported products
            $config->resultCallback = function (\Mageseller\ProductImport\Api\Data\Product $product) use (&$process, &$productIdsToReindex, &$importer) {
                $time = round(microtime(true) - $this->start, 2);
                if ($product->isOk()) {
                    $productIdsToReindex[] = $product->id;
                    $message = sprintf("%s: success! sku = %s, id = %s  ( $time s)\n", $product->lineNumber, $product->getSku(), $product->id);
                } else {
                    $message = sprintf("%s: failed! sku = %s error = %s ( $time s)\n", $product->lineNumber, $product->getSku(), implode('; ', $product->getErrors()));
                }
                if (isset($message)) {
                    $process->output($message);
                }
                $this->start = microtime(true);
            };
            $importer = $this->importerFactory->createImporter($config);
            $isFlush = false;
            $processResource = $this->processResourceFactory->create();
            $processResource->save($process);
            $i = 0; // Line number
            $j = 0; // Line number
            foreach ($items as $item) {
                $sku = (string)$item->ItemDetail->ManufacturerPartID;
                try {
                    ++$i;
                    $this->start = microtime(true);

                    $this->processImport($item, $j, $importer, $since, $process);

                    $time = round(microtime(true) - $this->start, 2);
                    $isFlush = false;
                    if ($i % 5 === 0) {
                        $processResource->save($process);
                        /*if (!$isFlush) {
                            $this->start = microtime(true);
                            $importer->flush();
                            $importer = $this->importerFactory->createImporter($config);
                            $isFlush = true;
                        }*/
                    }
                } catch (WarningException $e) {
                    $message = __("Warning on sku %1: {$e->getMessage()}", $sku);
                    $process->output($message);
                } catch (\Exception $e) {
                    $error = __("Error for sku %1: {$e->getMessage()}", $sku);

                    $process->output($error);
                }
            }
        } catch (\Exception $e) {
            $process->fail($e->getMessage());
            throw $e;
        } finally {
            //if (!$isFlush) {
            $this->start = microtime(true);
            $importer->flush();
            //}
            // Reindex
            $process->output(__('Reindexing...'), true);
            $this->reindexProducts($productIdsToReindex);
            //$this->indexer->reindex();
        }

        $process->output(__('Done!'));
    }
    private function processImport(&$data, &$j, &$importer, &$since, &$process)
    {
        $sku = (string)$data->ItemDetail->ManufacturerPartID;
        $price = (string)$data->ItemDetail->UnitPrice;
        $price = floatval(preg_replace('/[^\d.]/', '', $price));
        $taxRate = (string)$data->ItemDetail->TaxRate;
        $updatedAt = (string)$data->UpdatedAt;
        $currentUpdateAT = date_parse($updatedAt);
        $oldUpdateAt = date_parse($since->format('Y-m-d H:i:s'));

        $availibilities = $data->Availability->Warehouse;
        $quantity = 0;
        foreach ($availibilities as $avail) {
            $v = (string)$avail->StockLevel;
            if (strtolower($v) == 'call') {
                $quantity = '999999';
                continue;
            }
            if (strtolower($v) == 'on order') {
                $quantity = '999999';
                continue;
            }
            $trimmedQty = trim($v, '+');
            if ($trimmedQty) {
                $quantity =  $trimmedQty;
            }
        }

        $product = new SimpleProduct($sku);
        $product->lineNumber = $j + 1;

        $global = $product->global();
        $global->setPrice($price);
        if (isset($data->ItemDetail->RRP)) {
            $specialPrice = (string)$data->ItemDetail->RRP;
            $specialPrice = $specialPrice ? floatval(preg_replace('/[^\d.]/', '', $specialPrice)) : null;
            $global->setSpecialPrice($specialPrice);
        }
        $isInStock = $quantity > 0;
        $product->sourceItem("default")->setQuantity($quantity);
        $product->sourceItem("default")->setStatus($isInStock);

        $stock = $product->defaultStockItem();
        $stock->setQty($quantity);
        $stock->setIsInStock($isInStock);
        $stock->setMaximumSaleQuantity(10000.0000);
        $stock->setNotifyStockQuantity(1);
        $stock->setManageStock(true);
        $stock->setQuantityIncrements(1);

        if (isset($this->existingSkus[$sku])) {
            if ($oldUpdateAt <= $currentUpdateAT) {
                $j++;
                $importer->importSimpleProduct($product);
            }
            return;
        }

        $name = (string)$data->ItemDetail->Title;
        $description = (string)$data->ItemDetail->Description;
        $taxClassName = 'Taxable Goods';
        $attributeSetName = "Default";

        $product->setAttributeSetByName($attributeSetName);
        $product->setWebsitesByCode(['base']);

        $global->setName($name);
        $global->setDescription($description);
        $global->setPrice($price);
        $global->setTaxClassName($taxClassName);
        $global->setStatus(ProductStoreView::STATUS_ENABLED);
        $global->setVisibility(ProductStoreView::VISIBILITY_BOTH);
        $global->generateUrlKey();



        if (isset($data->ItemDetail->ShippingWeight)) {
            $weight = (string)$data->ItemDetail->ShippingWeight;
            $global->setWeight($weight);
        }
        if (isset($data->ItemDetail->Height)) {
            $height = (string)$data->ItemDetail->Height;
            $global->setCustomAttribute('ts_dimensions_height', $height);
        }
        if (isset($data->ItemDetail->Width)) {
            $width = (string)$data->ItemDetail->Width;
            $global->setCustomAttribute('ts_dimensions_width', $width);
        }
        if (isset($data->ItemDetail->Length)) {
            $length = (string)$data->ItemDetail->Length;
            $global->setCustomAttribute('ts_dimensions_length', $length);
        }
        $global->setSelectAttribute('supplier', self::SUPPLIER);

        $categories = $this->parseObject($data->ItemDetail->Classifications->Classification);
        unset($categories['@attributes']);
        $categoryIds = [];
        foreach ($categories as $categoryName) {
            $existingXitCategoryIds = $this->existingXitCategoryIds[$categoryName] ?? [];
            if ($existingXitCategoryIds) {
                $categoryIds = array_merge($categoryIds, explode(",", $existingXitCategoryIds));
            }
        }
        if ($categoryIds) {
            $categoryIds = array_filter(array_unique($categoryIds));
            $product->addCategoryIds($categoryIds);
        }

        //brochure_url
        /*// German eav attributes
        $german = $product->storeView('de_store');
        $german->setName($line[3]);
        $german->setPrice($line[4]);*/

        $j++;
        $importer->importSimpleProduct($product);
    }
    private function getExistingXitCategoryIds($allCategoryNames)
    {
        if (empty($allCategoryNames)) {
            return [];
        }
        $xitCategoryTable = $this->db->getFullTableName(self::XIT_CATEGORY_TABLE);
        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->addAttributeToSelect('xit_category_ids', 'left');
        $select = $categoryCollection->getSelect();
        $select->reset(Select::COLUMNS)->columns('GROUP_CONCAT(`e`.`entity_id`)');
        $select->where("FIND_IN_SET(`{$xitCategoryTable}`.`xitcategory_id`, `at_xit_category_ids`.`value`)");
        return $this->db->fetchMap("
            SELECT `name`, ({$select}) as `category_ids`  
            FROM `{$xitCategoryTable}`
            WHERE BINARY `name` IN (" . $this->db->getMarks($allCategoryNames) . ")
        ", array_values($allCategoryNames));
    }
    /**
     * Returns an sku => id map for all existing skus.
     *
     * @param string[] $skus
     * @return array
     */
    public function getExistingSkus(array $skus)
    {
        if (empty($skus)) {
            return [];
        }

        return $this->db->fetchMap("
            SELECT `sku`, `entity_id` 
            FROM `{$this->metaData->productEntityTable}`
            WHERE BINARY `sku` IN (" . $this->db->getMarks($skus) . ")
        ", array_values($skus));
    }
    /**
     * Initiate product reindex by product ids
     *
     * @param array $productIdsToReindex
     * @return void
     */
    private function reindexProducts($productIdsToReindex = [])
    {
        $indexer = $this->indexerRegistry->get(\Magento\Catalog\Model\Indexer\Product\Category::INDEXER_ID);
        if (is_array($productIdsToReindex) && count($productIdsToReindex) > 0 && !$indexer->isScheduled()) {
            $indexer->reindexList($productIdsToReindex);
            //$this->_productEavIndexerProcessor->reindexList($productIdsToReindex);
            $this->stockIndexerProcessor->reindexList($productIdsToReindex);
            $this->priceIndexer->reindexList($productIdsToReindex);
            $this->flushCacheByProductIds->execute($productIdsToReindex);
        }
    }

    public function parseValue($value)
    {
        return isset($value) ? trim($value) : "";
    }

    public function cleanData($a)
    {
        if (is_numeric($a)) {
            $a = preg_replace('/[^0-9,]/s', '', $a);
        }

        return $a;
    }
    public function parseObject($value)
    {
        return isset($value) ? is_object($value) ? array_filter(json_decode(json_encode($value), true), function ($value) {
            return !is_array($value) && $value !== '';
        }) : $value : [];
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
    public function downloadPdfFile($source, $fileName)
    {
        $downloadFolderPdf = $this->_dirReader->getPath('pub') . '/media/' . self::PDF_FOLDER;
        //check if directory exists
        if (!is_dir($downloadFolderPdf)) {
            $this->fileFactory->mkdir($downloadFolderPdf, 0775);
        }
        $filepath = $downloadFolderPdf . '/' . $fileName;
        //@todo check if file is changed or not
        if (!$this->fileFactory->fileExists($filepath)) {
            $ch = curl_init($source);
            $fp = fopen($filepath, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        }
    }
}
