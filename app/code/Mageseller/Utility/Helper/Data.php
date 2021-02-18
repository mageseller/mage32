<?php namespace Mageseller\Utility\Helper;

use Magento\Catalog\Model\Product as ProductEntityType;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Select;
use Magento\InventoryCache\Model\FlushCacheByProductIds;
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
        EavConfig $eavConfig
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
        $options = $this->db->fetchMap(
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
        return $options;
    }
    public function getAllSkus($supplierName)
    {
        //$supplierName = 'xitdistribution';
        $option = $this->loadOptionValues('supplier');
        $optionId = $option[$supplierName] ?? "";
        if ($optionId) {
            $productCollection = $this->_productCollectionFactory->create();
            $productCollection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED, 'left');
            $productCollection->addAttributeToFilter('supplier', ['eq' => $optionId ], 'left');
            $select = $productCollection->getSelect();
            $select->reset(Select::COLUMNS)->columns(['sku']);
            return $this->db->fetchSingleColumn($select);
        }
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
        $productCollection->getSelect()->where("sku IN (?)", $skus);
        return $this->db->fetchMap($productCollection->getSelect());
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
        foreach ($backorderOption as $option) {
            $backorderOptions[] = $option['options'];
        }
        return $backorderOptions;
    }
    public function getExistingCategoryIds($type)
    {
        $supplierCategoryTable = $this->db->getFullTableName("mageseller_" . $type . "import_" . $type . "category");
        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->addAttributeToSelect($type . '_category_ids', 'left');
        $select = $categoryCollection->getSelect();
        $select->reset(Select::COLUMNS)->columns('GROUP_CONCAT(`e`.`entity_id`)');
        $select->where("FIND_IN_SET(`{$supplierCategoryTable}`.`" . $type . "category_id`, `at_" . $type . "_category_ids`.`value`)");

        return $this->db->fetchMap(
            "SELECT LOWER(CONCAT(name,'" . self::SEPERATOR . "',parent_name)), ({$select}) as `category_ids`  
            FROM `{$supplierCategoryTable}`"
        );
    }
    public function getExistingCategoryAttributeIds($type)
    {
        $supplierCategoryTable = $this->db->getFullTableName("mageseller_" . $type . "import_" . $type . "category");
        $eavAttributeTable = $this->db->getFullTableName('eav_attribute');
        return $this->db->fetchMap(
            "SELECT LOWER(CONCAT(name,'" . self::SEPERATOR . "',parent_name)), `attribute_code`  
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
}
