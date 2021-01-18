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
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Mageseller\Process\Helper\Product\Import\Url;
use Mageseller\Process\Model\Process;
use Mageseller\Process\Model\Product\Import\Indexer\Indexer;
use Mageseller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Mageseller\ProductImport\Api\Data\ProductStoreView;
use Mageseller\ProductImport\Api\Data\SimpleProduct;
use Mageseller\ProductImport\Api\ImportConfig;
use Mageseller\XitImport\Helper\Product\Import\Inventory as InventoryHelper;

class ImageHelper extends AbstractHelper
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
     * @var \Mageseller\ProductImport\Api\ImporterFactory
     */
    private $importerFactory;
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
     * @var
     */
    private $mediaUrl;

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
        \Mageseller\ProductImport\Api\ImporterFactory $importerFactory,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\CatalogInventory\Model\Indexer\Stock\Processor $stockIndexerProcessor,
        \Magento\Catalog\Model\Indexer\Product\Price\Processor $priceIndexer,
        \Magento\Catalog\Model\Indexer\Product\Eav\Processor $productEavIndexerProcessor,
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
        $this->importerFactory = $importerFactory;
        $this->indexerRegistry = $indexerRegistry;
        $this->stockIndexerProcessor = $stockIndexerProcessor;
        $this->priceIndexer = $priceIndexer;
        $this->_productEavIndexerProcessor = $productEavIndexerProcessor;
        $this->mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function processProductImages($items, Process $process, $since, $sendReport = true)
    {
        $isFlush = false;
        try {
            $oldUpdateAt = $since ? date_parse($since->format('Y-m-d H:i:s')) : 0;
            // Disable or not the indexing when UpdateOnSave mode
            $this->indexer->initIndexers();
            $config = new ImportConfig();
            $config->duplicateUrlKeyStrategy = ImportConfig::DUPLICATE_KEY_STRATEGY_ADD_SERIAL;
            $config->existingImageStrategy = ImportConfig::EXISTING_IMAGE_STRATEGY_CHECK_IMPORT_DIR;
            $config->existingImageStrategy = ImportConfig::EXISTING_IMAGE_STRATEGY_HTTP_CACHING;
            $productIdsToReindex = [];
            // a callback function to postprocess imported products
            $config->resultCallback = function (\Mageseller\ProductImport\Api\Data\Product $product) use (&$process,&$time,&$productIdsToReindex,&$importer) {
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

            $processResource = $this->processResourceFactory->create();
            $processResource->save($process);
            $i = 0; // Line number
            $j = 0; // Line number
            foreach ($items as $item) {
                try {
                    ++$i;
                    $this->start = microtime(true);

                    //$this->importImages($item, $j, $importer);
                    $sku =  (string) $item->ItemDetail->ManufacturerPartID;
                    $flag = false;
                    $product = new SimpleProduct($sku);
                    if (isset($item->Images->Image->URL)) {
                        foreach ($item->Images as $node) {
                            $updatedAt = (string)$node->Image->UpdatedAt;
                            $currentUpdateAT = date_parse($updatedAt);
                            if ($oldUpdateAt <= $currentUpdateAT) {
                                $imageUrl = (string) $node->Image->URL;
                                if ($imageUrl) {
                                    $image = $product->addImage($imageUrl);
                                    $product->global()->setImageRole($image, ProductStoreView::BASE_IMAGE);
                                    $product->global()->setImageRole($image, ProductStoreView::THUMBNAIL_IMAGE);
                                    $product->global()->setImageRole($image, ProductStoreView::SMALL_IMAGE);
                                    $flag = true;

                                }
                            }
                        }
                    }
                    if (isset($data->Brochures->Brochure->URL)) {
                        $brochureSourceUrl = (string)$data->Brochures->Brochure->URL;
                        $process->output(__("Downloading Brochure : $brochureSourceUrl"));
                        $path_parts = pathinfo($brochureSourceUrl);
                        if ($brochureSourceUrl) {
                            $brochureUrl = $this->mediaUrl . self::PDF_FOLDER . '/' . $path_parts['basename'];
                            $this->downloadPdfFile($brochureSourceUrl, $path_parts['basename']);
                            $product->global()->setCustomAttribute('brochure_url', $brochureUrl);
                        }
                        $flag = true;
                    }
                    if ($flag) {
                        $product->lineNumber = $j + 1;
                        $j++;
                        $importer->importSimpleProduct($product);
                    }
                    $time = round(microtime(true) - $this->start, 2);
                    $isFlush = false;
                    if ($i % 5 === 0) {
                        $processResource->save($process);
                        if (!$isFlush) {
                            $this->start = microtime(true);
                            $importer->flush();
                            $importer = $this->importerFactory->createImporter($config);
                            $isFlush = true;
                        }
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
            if (!$isFlush) {
                $this->start = microtime(true);
                $importer->flush();
            }
            // Reindex
            $process->output(__('Reindexing...'), true);
            $this->reindexProducts($productIdsToReindex);
            //$this->indexer->reindex();
        }

        $process->output(__('Done!'));
    }
    private function importImages(&$data, &$j, &$importer)
    {
        $sku =  (string) $data->ItemDetail->ManufacturerPartID;
        $name = (string) $data->ItemDetail->Title;
        $price =  (string) $data->ItemDetail->UnitPrice;
        $price = floatval(preg_replace('/[^\d.]/', '', $price));
        $description = (string) $data->ItemDetail->Description;

        $taxRate = (string) $data->ItemDetail->TaxRate;
        $updatedAt = (string) $data->UpdatedAt;
        $taxClassId = 2;
        $attributeSetId = 4;
        $product = new SimpleProduct($sku);
        $product->lineNumber = $j + 1;
        $product->setAttributeSetByName("Default");
        $product->addCategoryIds([1, 2, 20,38,23]);
        $product->setWebsitesByCode(['base']);
        $product->sourceItem("default")->setQuantity(100);
        $product->sourceItem("default")->setStatus(1);
        if (isset($data->Images->Image->URL)) {
            $imageUrl = "{$data->Images->Image->URL}";
            if ($imageUrl) {
                /*$image = $product->addImage($imageUrl);
                //$product->global()->setImageGalleryInformation($image, "First duck", 1, true);
                $product->global()->setImageRole($image, ProductStoreView::BASE_IMAGE);*/
            }
        }

        // global eav attributes
        $global = $product->global();
        $global->setName($name);
        $global->setDescription($description);
        $global->setPrice($price);
        $global->setTaxClassName('Taxable Goods');
        $global->setStatus(ProductStoreView::STATUS_ENABLED);
        $global->setWeight("1");
        $global->setVisibility(ProductStoreView::VISIBILITY_BOTH);
        $global->generateUrlKey();

        $stock = $product->defaultStockItem();
        $stock->setQty(100);
        $stock->setIsInStock(true);
        $stock->setMaximumSaleQuantity(10000.0000);
        $stock->setNotifyStockQuantity(1);
        $stock->setManageStock(true);
        $stock->setQuantityIncrements(1);

        /*// German eav attributes
        $german = $product->storeView('de_store');
        $german->setName($line[3]);
        $german->setPrice($line[4]);*/
        $j++;
        $importer->importSimpleProduct($product);
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
    public function cleanData($a)
    {
        if (is_numeric($a)) {
            $a = preg_replace('/[^0-9,]/s', '', $a);
        }

        return $a;
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
        }
    }
}
