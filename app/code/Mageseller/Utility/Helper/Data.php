<?php namespace Mageseller\Utility\Helper;

use Magento\Catalog\Model\Product as ProductEntityType;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Config\Model\ResourceModel\Config as MagentoConfig;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Select;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryCache\Model\FlushCacheByProductIds;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Store\Model\ScopeInterface;
use Mageseller\ProductImport\Model\Persistence\Magento2DbConnection;
use Mageseller\ProductImport\Model\Resource\MetaData;

class Data extends AbstractHelper
{
    const SEPERATOR = " ---|--- ";

    /**
     * @var Magento2DbConnection
     */
    protected $db;
    /**
     * @var MetaData
     */
    protected $metaData;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    protected $_eavAttribute;
    private $eavAttributes;
    /**
     * @var AttributeCollectionFactory
     */
    private $attributeFactory;
    /**
     * @var \Mageseller\Customization\Model\ResourceModel\Devices\Collection
     */
    private $optionReplacementCollection;
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;
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
     * @var \Magento\Store\Model\StoreManager
     */
    private $_storeManager;

    private $stores;

    private $websiteIds;
    /**
     * @var ProductCollectionFactory
     */
    private $_productCollectionFactory;
    /**
     * @var EavConfig
     */
    private $eavConfig;
    /**
     * @var MagentoConfig
     */
    protected $configuration;
    /**
     * @var Manager
     */
    protected $moduleManager;
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * Core file storage database
     *
     * @var Database
     */
    protected $fileStorageDb;
    /**
     * @var array
     */
    protected $marginOptions;
    /**
     * @var array
     */
    protected $optionsValues;

    /**
     * Data constructor.
     * @param Context $context
     * @param Magento2DbConnection $db
     * @param MetaData $metaData
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $eavAttribute
     * @param AttributeCollectionFactory $attributeFactory
     * @param \Mageseller\Customization\Model\ResourceModel\Devices\Collection $optionReplacementCollection
     * @param CollectionFactory $categoryCollectionFactory
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param \Magento\CatalogInventory\Model\Indexer\Stock\Processor $stockIndexerProcessor
     * @param \Magento\Catalog\Model\Indexer\Product\Price\Processor $priceIndexer
     * @param \Magento\Catalog\Model\Indexer\Product\Eav\Processor $productEavIndexerProcessor
     * @param FlushCacheByProductIds $flushCacheByProductIds
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param ProductCollectionFactory $productCollectionFactory
     * @param EavConfig $eavConfig
     * @param MagentoConfig $configuration
     */
    public function __construct(
        Context $context,
        Magento2DbConnection $db,
        MetaData $metaData,
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $eavAttribute,
        AttributeCollectionFactory $attributeFactory,
        \Mageseller\Customization\Model\ResourceModel\Devices\Collection $optionReplacementCollection,
        CollectionFactory $categoryCollectionFactory,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\CatalogInventory\Model\Indexer\Stock\Processor $stockIndexerProcessor,
        \Magento\Catalog\Model\Indexer\Product\Price\Processor $priceIndexer,
        \Magento\Catalog\Model\Indexer\Product\Eav\Processor $productEavIndexerProcessor,
        FlushCacheByProductIds $flushCacheByProductIds,
        \Magento\Store\Model\StoreManager $storeManager,
        ProductCollectionFactory $productCollectionFactory,
        EavConfig $eavConfig,
        MagentoConfig $configuration,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager
    ) {
        parent::__construct($context);
        $this->db = $db;
        $this->metaData = $metaData;
        $this->_eavAttribute = $eavAttribute;
        $this->attributeFactory = $attributeFactory;
        $this->optionReplacementCollection = $optionReplacementCollection;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->indexerRegistry = $indexerRegistry;
        $this->stockIndexerProcessor = $stockIndexerProcessor;
        $this->priceIndexer = $priceIndexer;
        $this->_productEavIndexerProcessor = $productEavIndexerProcessor;
        $this->flushCacheByProductIds = $flushCacheByProductIds;
        $this->_storeManager = $storeManager;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->eavConfig = $eavConfig;
        $this->configuration = $configuration;
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
        if ($this->moduleManager->isEnabled('Thai_S3')) {
            $this->fileStorageDb = $this->objectManager->get(\Magento\MediaStorage\Helper\File\Storage\Database::class);
        }
    }
    /**
     * Get config value
     * @param $configPath
     * @param null $store
     * @return mixed
     */
    public function getConfigValue($configPath, $store = null)
    {
        return $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get serialized config value
     * temporarily solution to get unserialized config value
     * should be deprecated in 2.3.x
     *
     * @param $configPath
     * @param null $store
     * @return mixed
     */
    public function getSerializedConfigValue($configPath, $store = null)
    {
        $value = $this->getConfigValue($configPath, $store);

        if (empty($value)) {
            return false;
        }

        if ($this->isSerialized($value)) {
            $unserializer = ObjectManager::getInstance()->get(\Magento\Framework\Unserialize\Unserialize::class);
        } else {
            $unserializer = ObjectManager::getInstance()->get(\Magento\Framework\Serialize\Serializer\Json::class);
        }

        return $unserializer->unserialize($value);
    }

    /**
     * Check if value is a serialized string
     *
     * @param string $value
     * @return boolean
     */
    private function isSerialized($value)
    {
        return (boolean) preg_match('/^((s|i|d|b|a|O|C):|N;)/', $value);
    }

    public function loadOptionValues(string $attributeCode)
    {
        if (!$this->optionsValues) {
            $this->optionsValues = $this->db->fetchMap(
                "
            SELECT V.`value`, O.`option_id`
            FROM {$this->metaData->attributeTable} A
            INNER JOIN {$this->metaData->attributeOptionTable} O ON O.attribute_id = A.attribute_id
            INNER JOIN {$this->metaData->attributeOptionValueTable} V ON V.option_id = O.option_id
            WHERE A.`attribute_code` = ? AND A.`entity_type_id` = ? AND V.store_id = 0
        ",
                [
                    $attributeCode,
                    $this->metaData->productEntityTypeId
                ]
            );
        }

        return $this->optionsValues;
    }
    public function getAllSkus($supplierName)
    {
        //$supplierName = 'xitdistribution';
        $option = $this->loadOptionValues('supplier');
        $optionId = $option[$supplierName] ?? "";
        if ($optionId) {
            $productCollection = $this->_productCollectionFactory->create();
            $productCollection->addAttributeToFilter('status', Status::STATUS_ENABLED, 'left');
            $productCollection->addAttributeToFilter('supplier', ['eq' => $optionId ], 'left');

            $select = $productCollection->getSelect();
            $select->reset(Select::COLUMNS)->columns(['sku']);
            return $this->db->fetchSingleColumn($select);
        }
        return [];
    }
    public function getAllSkusWithSupplierPartId($supplierName)
    {
        $option = $this->loadOptionValues('supplier');
        $optionId = $option[$supplierName] ?? "";
        if ($optionId) {
            $productCollection = $this->_productCollectionFactory->create();
            $productCollection->addAttributeToFilter('status', Status::STATUS_ENABLED, 'left');
            $productCollection->addAttributeToFilter('supplier', ['eq' => $optionId ], 'left');
            $productCollection->addAttributeToSelect('supplier_product_id', 'left');
            $select = $productCollection->getSelect();
            $select->reset(Select::COLUMNS);

            $select->columns(['supplier_product_id' => new \Zend_Db_Expr("REPLACE(LTRIM(REPLACE(at_supplier_product_id.value, '0', ' ')),' ', '0')"),'sku']);
            return $this->db->fetchMap($select);
        }
        return [];
    }
    /**
     * Returns an sku => id map for all existing skus.
     *
     * @param  string[] $skus
     * @return array
     */
    public function getExistingSkus(array $skus)
    {
        if (empty($skus)) {
            return [];
        }

        return $this->db->fetchMap(
            "
            SELECT `sku`, `entity_id`
            FROM `{$this->metaData->productEntityTable}`
            WHERE BINARY `sku` IN (" . $this->db->getMarks($skus) . ")
        ",
            array_values($skus)
        );
    }
    /**
     * Returns an sku => supplier_id map for all existing skus.
     *
     * @param  string[] $skus
     * @return array
     */
    public function getExistingSkusWithSupplier(array $skus)
    {
        if (empty($skus)) {
            return [];
        }
        $productCollection = $this->_productCollectionFactory->create();
        $productCollection->getSelect()->reset(Select::COLUMNS)->columns(['sku']);
        $productCollection->addAttributeToSelect('supplier', 'left');
        $productCollection->addAttributeToSelect('price', 'left');
        $productCollection->getSelect()->where("e.sku IN (?)", $skus);
        $connection = $productCollection->getConnection();
        $productCollection->getSelect()
            ->joinLeft(
                [ 'isi' => $connection->getTableName('inventory_source_item')],
                'isi.sku=e.sku',
                [
                    'source_code' => new \Zend_Db_Expr('GROUP_CONCAT(source_code)'),
                    'quantity' => new \Zend_Db_Expr('GROUP_CONCAT(quantity)')
                ]
            )
            ->joinLeft(
                [ 'ccp' => $connection->getTableName('catalog_category_product')],
                'ccp.product_id=e.entity_id',
                [
                    'category_ids' => new \Zend_Db_Expr('GROUP_CONCAT(category_id)'),
                ]
            )->group('e.entity_id')
            ->group('e.sku');
        return $connection->fetchAssoc($productCollection->getSelect());
    }
    public function getAttributeCode($attributeId)
    {
        return $this->getAttributeById($attributeId)->getAttributeCode();
    }
    public function getAttributeById($attributeId)
    {
        if (!isset($this->eavAttributes[$attributeId])) {
            $this->eavAttributes[$attributeId] = $this->_eavAttribute->load($attributeId);
        }
        return $this->eavAttributes[$attributeId];
    }
    public function getOptionReplaceMents()
    {
        $collection = $this->attributeFactory->create();
        $collection
            ->addFieldToFilter('entity_type_id', $this->eavConfig->getEntityType(ProductEntityType::ENTITY)->getEntityTypeId())
            ->addFieldToFilter('frontend_input', 'select')
            ->setOrder('attribute_id', 'desc');
        $optionReplacementsCollection = $this->optionReplacementCollection;
        $select = $optionReplacementsCollection->getSelect()
            ->reset('columns')
            ->columns(['option_id','alternate_options']);
        $optionReplacementsTableData = $this->db->fetchMap($select);
        $attributeOptionReplaceMents = [];
        foreach ($collection as $attribute) {
            $optionReplacements = [];
            $options = $attribute->getSource()->getAllOptions();
            foreach ($options as $option) {
                $value = $option['value'];  // Value
                if (isset($optionReplacementsTableData[$value])) {
                    $labels = explode(",", $optionReplacementsTableData[$value]);
                    foreach ($labels as $label) {
                        $optionReplacements[$label] = $value;
                    }
                }
            }
            $attributeOptionReplaceMents[$attribute[AttributeInterface::ATTRIBUTE_CODE]] = $optionReplacements;
        }
        return $attributeOptionReplaceMents;
    }
    /**
     * Return string with json configuration
     *
     * @return string
     */
    public function getBackOrderValues($type)
    {
        $backorderOption = $this->getSerializedConfigValue($type . '/product/backorder_option');
        $backorderOptions = [];
        if (isset($backorderOption) && $backorderOption) {
            foreach ($backorderOption as $option) {
                $backorderOptions[] = $option['options'];
            }
        }
        return $backorderOptions;
    }

    /**
     * Return string with json configuration
     *
     * @param $type
     * @return string
     */
    public function getMargin($type)
    {
        if (!$this->marginOptions) {
            $marginOption = $this->getSerializedConfigValue($type . '/product/price_margin');
            $this->marginOptions = [];
            if (isset($marginOption) && $marginOption) {
                $this->marginOptions = array_values($marginOption);
            }
        }
        return $this->marginOptions;
    }
    public function calcPriceMargin($cost, $type)
    {
        $price = $cost;
        $marginOptions = $this->getMargin($type);
        $priceMargin = [];
        foreach ($marginOptions as $option) {
            $min = $option['min'] ?? 0;
            $max = $option['max'] ?? 0;
            if ($cost > $min && $cost < $max) {
                $priceMargin = $option;
                break;
            }
        }
        $sign = $priceMargin['sign'] ?? "";
        $priceMarginValue = $priceMargin['value'] ?? 0;

        if ($sign && $priceMarginValue) {
            if ($sign == '%') {
                $pricePlusMarginValue = $priceMargin['addition'] ?? 0;
                if (!$pricePlusMarginValue) {
                    $pricePlusMarginValue = 0;
                }
                $price = $cost + ($cost * $priceMarginValue/100) + floatval($pricePlusMarginValue);
            } elseif ($sign == '+') {
                $price = $cost + $priceMarginValue;
            } elseif ($sign == '-') {
                $price = $cost - $priceMarginValue;
            }
        }
        return $price;
    }
    public function getExistingCategoryIds($type)
    {
        $supplierCategoryTable = $this->db->getFullTableName("mageseller_" . $type . "import_" . $type . "category");
        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->addAttributeToSelect($type . '_category_ids', 'left');
        $select = $categoryCollection->getSelect();
        $select->reset(Select::COLUMNS)->columns('GROUP_CONCAT(`e`.`entity_id`)');
        $select->where("FIND_IN_SET(`{$supplierCategoryTable}`.`" . $type . "category_id`, `at_" . $type . "_category_ids`.`value`)");

        if ($type == "ingrammicro") {
            return $this->db->fetchMap(
                "SELECT {$type}category_id, ({$select}) as `category_ids`
            FROM `{$supplierCategoryTable}`"
            );
        }
        return $this->db->fetchMap(
            "SELECT LOWER(CONCAT(name,'" . self::SEPERATOR . "',parent_name)), ({$select}) as `category_ids`
            FROM `{$supplierCategoryTable}`"
        );
    }
    public function getExistingCategoryAttributeIds($type)
    {
        $supplierCategoryTable = $this->db->getFullTableName("mageseller_" . $type . "import_" . $type . "category");
        $eavAttributeTable = $this->db->getFullTableName('eav_attribute');
        $key = "LOWER(CONCAT(name,'" . self::SEPERATOR . "',parent_name))";
        if ($type == "ingrammicro") {
            $key = "{$type}category_id";
        }
        return $this->db->fetchMap(
            "SELECT $key, `attribute_code`
            FROM `{$supplierCategoryTable}` AS `" . $type . "`
            LEFT JOIN `{$eavAttributeTable}` AS `eav`
            ON `" . $type . "`.`attribute_id` = `eav`.`attribute_id`
            WHERE `" . $type . "`.`attribute_id` IS NOT NULL"
        );
    }
    /**
     * Initiate product reindex by product ids
     *
     * @param  array $productIdsToReindex
     * @return void
     */
    public function reindexProducts($productIdsToReindex = [])
    {
        if (is_array($productIdsToReindex) && count($productIdsToReindex) > 0) {
            $indexer = $this->indexerRegistry->get(\Magento\Catalog\Model\Indexer\Product\Category::INDEXER_ID);
            if (!$indexer->isScheduled()) {
                $indexer->reindexList($productIdsToReindex);
            }
            if (!$this->_productEavIndexerProcessor->isIndexerScheduled()) {
                $this->_productEavIndexerProcessor->reindexList($productIdsToReindex);
            }
            if (!$this->stockIndexerProcessor->isIndexerScheduled()) {
                $this->stockIndexerProcessor->reindexList($productIdsToReindex);
            }
            if (!$this->priceIndexer->isIndexerScheduled()) {
                $this->priceIndexer->reindexList($productIdsToReindex);
            }
            $searchIndexer =  $this->indexerRegistry->get(\Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID);
            if (!$searchIndexer->isScheduled()) {
                $searchIndexer->reindexList($productIdsToReindex);
            }
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
        return isset($value) ? is_object($value) ? array_filter(
            json_decode(json_encode($value), true),
            function ($value) {
                return !is_array($value) && $value !== '';
            }
        ) : $value : [];
    }

    /**
     * Returns website ids to enable for product import
     *
     * @return array
     */
    public function getWebsiteIds()
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
     * @return StoreInterface[]
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

    /********************************************/
    /**
     * @return int
     */
    public function getCurrentStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * @return int
     */
    public function getCurrentWebsiteId()
    {
        return $this->_storeManager->getStore()->getWebsiteId();
    }

    /**
     * Returns a config flag
     *
     * @param  string $path
     * @param  mixed  $store
     * @return bool
     */
    public function getFlag($path, $store = null)
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Returns store locale
     *
     * @param  mixed $store
     * @return string
     */
    public function getLocale($store = null)
    {
        return $this->getValue('general/locale/code', $store);
    }

    /**
     * Get tax class id specified for shipping tax estimation
     *
     * @param  mixed $store
     * @return int
     */
    public function getShippingTaxClass($store = null)
    {
        return $this->getValue(\Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $store);
    }

    /**
     * Reads the configuration directly from the database
     *
     * @param  string $path
     * @param  string $scope
     * @param  int    $scopeId
     * @return string|false
     */
    public function getRawValue($path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        $connection = $this->configuration->getConnection();

        $select = $connection->select()
            ->from($this->configuration->getMainTable(), 'value')
            ->where('path = ?', $path)
            ->where('scope = ?', $scope)
            ->where('scope_id = ?', $scopeId);

        return $connection->fetchOne($select);
    }

    /**
     * Returns a config value
     *
     * @param  string $path
     * @param  mixed  $store
     * @return mixed
     */
    public function getValue($path, $store = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Returns store name if defined
     *
     * @param  mixed $store
     * @return string
     */
    public function getStoreName($store = null)
    {
        return $this->getValue(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_NAME, $store);
    }

    /**
     * @return bool
     */
    public function isSingleStoreMode()
    {
        return $this->_storeManager->hasSingleStore();
    }
    /**
     * @param  string $entity
     * @param  mixed  $store
     * @return \DateTime|null
     */
    public function getSyncDate($type, $entity, $store = null)
    {
        //$type = "leadersystems";
        $path = "$type/$entity/last_sync_$entity";

        if (null === $store) {
            $date = $this->getRawValue($path);
        } else {
            $scopeId = $this->_storeManager->getStore($store)->getId();
            $date = $this->getRawValue($path, ScopeInterface::SCOPE_STORES, $scopeId);
        }

        return !empty($date) ? new \DateTime($date) : null;
    }

    /**
     * @return $this
     */
    protected function resetConfig()
    {
        $this->_storeManager->getStore()->resetConfig();

        return $this;
    }

    /**
     * @param  string $entity
     * @return $this
     */
    public function resetSyncDate($type, $entity)
    {
        $this->setValue("$type/$entity/last_sync_$entity", null);

        return $this->resetConfig();
    }

    /**
     * @param  string $entity
     * @param  string $time
     * @return $this
     */
    public function setSyncDate($type, $entity, $time = 'now')
    {
        $datetime = new \DateTime($time);
        $this->setValue("$type/$entity/last_sync_$entity", $datetime->format(\DateTime::ISO8601));

        return $this->resetConfig();
    }
    /**
     * Set a config value
     *
     * @param string $path
     * @param string $value
     * @param string $scope
     * @param int    $scopeId
     */
    public function setValue($path, $value, $scope = 'default', $scopeId = 0)
    {
        $this->configuration->saveConfig($path, $value, $scope, $scopeId);
    }

    /**
     * @param string $str
     * @return string
     */
    public function secureRip(string $str): string
    {
        return mb_convert_encoding($str, "UTF-8", "UTF-16LE");
    }
    public function getAllProductAttributes()
    {
        $collection = $this->attributeFactory->create();
        $collection
            ->addFieldToFilter('entity_type_id', $this->eavConfig->getEntityType(ProductEntityType::ENTITY)->getEntityTypeId())
            ->addFieldToFilter('frontend_input', 'select')
            ->setOrder('attribute_id', 'desc');

        $attributeCodes = [];
        foreach ($collection->getData() as $attributes) {
            $attributeCodes[] = [
                'id' => $attributes[AttributeInterface::ATTRIBUTE_ID],
                'value' => $attributes[AttributeInterface::ATTRIBUTE_CODE],
                'label' => $attributes[AttributeInterface::FRONTEND_LABEL]
            ];
        }
        return $attributeCodes;
    }
    public function uploadToS3($actualPath)
    {
        if ($this->fileStorageDb) {
            $this->fileStorageDb->saveFile($actualPath);
        }
    }
}
