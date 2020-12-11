<?php

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\DataObject;

require  '../app/bootstrap.php';

$params = $_SERVER;
$bootstrap = Bootstrap::create(BP, $params);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}
/**
 * Class GetEavAttributeDataWthoutLoad
 */
class GetEavAttributeDataWthoutLoad {
    /**
     * Entity attribute values per backend table to delete
     *
     * @var array
     */
    protected $_attributeValuesToDelete = [];

    /**
     * Entity attribute values per backend table to save
     *
     * @var array
     */
    protected $_attributeValuesToSave = [];
    /**
     * Array of describe attribute backend tables
     * The table name as key
     *
     * @var array
     */
    protected static $_attributeBackendTables = [];
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;
    /**
     * @var \Magento\Eav\Model\Config
     */
    private $_eavConfig;
    /**
     * @var \Magento\Eav\Model\Entity\AttributeFactory
     */
    private $_attributeFactory;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $_resource;
    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    private $_localeFormat;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $_connection;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Eav\Processor
     */
    protected $_productEavIndexerProcessor;

    /**
     * Application Event Dispatcher
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;
    private $entityType;
    /**
     * @var int
     */
    private $entityTypeId;
    protected  $_entityTable;
    private $_attributes;

    public function __construct(
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory ,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\Catalog\Model\Indexer\Product\Eav\Processor $productEavIndexerProcessor,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->_storeManager = $storeManager;
        $this->_eavConfig = $eavConfig;
        $this->_attributeFactory = $attributeFactory;
        $this->_resource = $resource;
        $this->_localeFormat = $localeFormat;
        $this->_connection = $resource->getConnection();
        $this->indexerRegistry = $indexerRegistry;
        $this->_productEavIndexerProcessor = $productEavIndexerProcessor;
        $this->_eventManager = $eventManager;
    }
    public function getAttribute($attributeCode,$entityType = ""){
        $entityType = $entityType ? $entityType :  $this->getEntityType();
        $entityId = $this->getEntityTypeId($entityType);
        if(!isset($_attributes[$attributeCode."_".$entityId])){
            $attribute = $this->_eavConfig->getAttribute($entityType, $attributeCode);
            $_attributes[$attributeCode."_".$entityId] = $attribute;
        }

        return $_attributes[$attributeCode."_".$entityId];
    }
    private function getEntityTypeId($entityType = "")
    {
        $entityTypeEav = $entityType ? $entityType : $this->entityTypeId;
        $entityTypeId = "";
        if(!$this->entityTypeId){
            switch ($entityTypeEav){
                case \Magento\Customer\Model\Customer::ENTITY:
                    $entityTypeId = 1;
                    break;
                case 'customer_address':
                    $entityTypeId = 2;
                    break;
                case \Magento\Catalog\Model\Category::ENTITY:
                    $entityTypeId = 3;
                    break;
                case \Magento\Catalog\Model\Product::ENTITY:
                default:
                    $entityTypeId = 4;
                    break;
            }
            if($entityType) return $entityType;
            $this->entityTypeId = $entityTypeId;
        }
        return  $this->entityTypeId;
    }
    public function getEntityTable($entityType){
        if(!$this->_entityTable){
            $table = $this->_eavConfig->getEntityType($entityType)->getEntityTable();
            $this->_entityTable = $this->_resource->getTableName($table);
        }

        return   $this->_entityTable;
    }
    public function getEntityType(){
        return $this->entityType;
    }
    public function getAttributeRawValue($entityId, $attribute, $store,$entityType = 'catalog_product')
    {
        $this->entityType = $entityType;
        if (!$entityId || empty($attribute)) {
            return false;
        }
        if (!is_array($attribute)) {
            $attribute = [$attribute];
        }

        $attributesData = [];
        $staticAttributes = [];
        $typedAttributes = [];
        $staticTable = null;
        $connection = $this->getConnection();

        foreach ($attribute as $item) {
            /* @var $attribute \Magento\Catalog\Model\Entity\Attribute */
            $item = $this->getAttribute($item);
            if (!$item) {
                continue;
            }
            $attributeCode = $item->getAttributeCode();
            $attrTable = $item->getBackend()->getTable();
            $isStatic = $item->getBackend()->isStatic();

            if ($isStatic) {
                $staticAttributes[] = $attributeCode;
                $staticTable = $attrTable;
            } else {
                /**
                 * That structure needed to avoid farther sql joins for getting attribute's code by id
                 */
                $typedAttributes[$attrTable][$item->getId()] = $attributeCode;
            }
        }

        /**
         * Collecting static attributes
         */
        if ($staticAttributes) {
            $select = $connection->select()->from(
                $staticTable,
                $staticAttributes
            )->join(
                ['e' => $this->getTable($this->getEntityTable($entityType))],
                'e.' . $this->getLinkField() . ' = ' . $staticTable . '.' . $this->getLinkField()
            )->where(
                'e.entity_id = :entity_id'
            );
            $attributesData = $connection->fetchRow($select, ['entity_id' => $entityId]);
        }

        /**
         * Collecting typed attributes, performing separate SQL query for each attribute type table
         */
        if ($store instanceof \Magento\Store\Model\Store) {
            $store = $store->getId();
        }

        $store = (int) $store;
        if ($typedAttributes) {
            foreach ($typedAttributes as $table => $_attributes) {
                $select = $connection->select()
                    ->from(['default_value' => $table], ['attribute_id'])
                    ->join(
                        ['e' => $this->getTable($this->getEntityTable($entityType))],
                        'e.' . $this->getLinkField() . ' = ' . 'default_value.' . $this->getLinkField(),
                        ''
                    )->where('default_value.attribute_id IN (?)', array_keys($_attributes))
                    ->where("e.entity_id = :entity_id")
                    ->where('default_value.store_id = ?', 0);

                $bind = ['entity_id' => $entityId];

                if ($store != $this->getDefaultStoreId()) {
                    $valueExpr = $connection->getCheckSql(
                        'store_value.value IS NULL',
                        'default_value.value',
                        'store_value.value'
                    );
                    $joinCondition = [
                        $connection->quoteInto('store_value.attribute_id IN (?)', array_keys($_attributes)),
                        "store_value.{$this->getLinkField()} = e.{$this->getLinkField()}",
                        'store_value.store_id = :store_id',
                    ];

                    $select->joinLeft(
                        ['store_value' => $table],
                        implode(' AND ', $joinCondition),
                        ['attr_value' => $valueExpr]
                    );

                    $bind['store_id'] = $store;
                } else {
                    $select->columns(['attr_value' => 'value'], 'default_value');
                }

                $result = $connection->fetchPairs($select, $bind);
                foreach ($result as $attrId => $value) {
                    $attrCode = $typedAttributes[$table][$attrId];
                    $attributesData[$attrCode] = $value;
                }
            }
        }

        if (is_array($attributesData) && sizeof($attributesData) == 1) {
            $attributesData = array_shift($attributesData);
        }

        return $attributesData === false ? false : $attributesData;
    }
    public function getTable($alias)
    {
        return $this->_resource->getTableName($alias);
    }
    public function getDefaultStoreId()
    {
        return \Magento\Store\Model\Store::DEFAULT_STORE_ID;
    }
    public function getLinkField(){
        return 'entity_id';
    }
    public function getStoreId($storeId = 0){
        $hasSingleStore = $this->_storeManager->hasSingleStore();
        $storeId = $hasSingleStore ? $this->getDefaultStoreId() : (int) $this->_storeManager->getStore($storeId)->getId();
        return $storeId;
    }
    public function getConnection(){
        return $this->_connection;
    }
    public function getCustomerAttributeRawValue($entityId, $attribute, $store = 0 ){
        return $this->getAttributeRawValue($entityId, $attribute, $store,\Magento\Customer\Model\Customer::ENTITY);
    }
    public function getProductAttributeRawValue($entityId, $attribute, $store = 0 ){
        return $this->getAttributeRawValue($entityId, $attribute, $store);
    }
    public function getCategoryAttributeRawValue($entityId, $attribute, $store = 0 ){
        return $this->getAttributeRawValue($entityId, $attribute, $store,\Magento\Catalog\Model\Category::ENTITY);
    }
    public function getCustomerAddressAttributeRawValue($entityId, $attribute, $store = 0 ){
        return $this->getAttributeRawValue($entityId, $attribute, $store,$entityType = 'customer_address');
    }
}
$getEavAttributeDataWthoutLoad = new GetEavAttributeDataWthoutLoad(
                            $objectManager->get('\Magento\Eav\Model\Entity\AttributeFactory'),
                            $objectManager->get('\Magento\Eav\Model\Config'),
                            $objectManager->get('\Magento\Framework\App\ResourceConnection'),
                            $objectManager->get('\Magento\Store\Model\StoreManagerInterface'),
                            $objectManager->get('\Magento\Framework\Locale\FormatInterface'),
                            $objectManager->get('\Magento\Framework\Indexer\IndexerRegistry'),
                            $objectManager->get('\Magento\Catalog\Model\Indexer\Product\Eav\Processor'),
                            $objectManager->get('\Magento\Framework\Event\ManagerInterface')
                        );


$start_time=microtime(true);

$storeId = $getEavAttributeDataWthoutLoad->getStoreId();
$entityId = 1;
$value = 0;
$entityId = 1;
$attributes = ['firstname','lastname'];
$attributeData = $getEavAttributeDataWthoutLoad->getCustomerAttributeRawValue($entityId,$attributes);
echo "<pre>";
print_r($attributeData);
printf("\r\n\r\nTotal time: %s\r\nMemory Used (current): %s\r\nMemory Used (max): %s", round(microtime(true) - $start_time, 4), formatBytes(memory_get_usage()), formatBytes(memory_get_peak_usage()));


