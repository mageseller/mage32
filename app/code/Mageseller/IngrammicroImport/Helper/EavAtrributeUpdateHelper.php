<?php
declare(strict_types=1);

namespace Mageseller\IngrammicroImport\Helper;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\DataObject;

class EavAtrributeUpdateHelper extends AbstractHelper
{
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
    private $_attributes;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\Catalog\Model\Indexer\Product\Eav\Processor $productEavIndexerProcessor,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        parent::__construct($context);
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
    public function getAttribute($attributeCode, $entityType = "")
    {
        $entityType = $entityType ? $entityType : $this->getEntityType();
        $entityId = $entityType ? $this->getEntityTypeId($entityType) : $this->getEntityTypeId();

        if (!isset($this->_attributes[$attributeCode . "_" . $entityId])) {
            $attribute = $this->_eavConfig->getAttribute($entityType, $attributeCode);
            if (!$attribute) {
                $attribute = $this->_attributeFactory->create();
                $attribute->loadByCode($entityId, $attributeCode);
                if (!$attribute->getId()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Invalid attribute %1', $attributeCode));
                }
                $entity = $this->_eavConfig->getEntityType($this->getEntityType())->getEntity();
                $attribute->setEntity($entity);
            }
            $this->_attributes[$attributeCode . "_" . $entityId] = $attribute;
        }
        return $this->_attributes[$attributeCode . "_" . $entityId];
    }

    public function getDefaultStoreId()
    {
        return \Magento\Store\Model\Store::DEFAULT_STORE_ID;
    }
    public function _updateAttributeForStore($entityId, $attribute, $value, $storeId)
    {

        //$this->_connection = $this->getConnection();
        $table = $attribute->getBackend()->getTable();

        if ($this->_storeManager->hasSingleStore()) {
            $storeId = $this->getDefaultStoreId();
            $this->getConnection()->delete(
                $table,
                [
                    'attribute_id = ?' => $attribute->getAttributeId(),
                    $this->getLinkField() . ' = ?' => $entityId,
                    'store_id <> ?' => $storeId
                ]
            );
        }
        $select = $this->getConnection()->select()
            ->from($table, 'value_id')
            ->where($this->getLinkField() . " = :entity_field_id")
            ->where('store_id = :store_id')
            ->where('attribute_id = :attribute_id');
        $bind = [
            'entity_field_id' => $entityId,
            'store_id' => $storeId,
            'attribute_id' => $attribute->getId(),
        ];
        $valueId = $this->getConnection()->fetchOne($select, $bind);
        /**
         * When value for store exist
         */
        if ($valueId) {
            $bind = ['value' => $this->_prepareValueForSave($value, $attribute)];
            $where = ['value_id = ?' => (int) $valueId];

            $this->getConnection()->update($table, $bind, $where);
        } else {
            $bind = [
                $this->getLinkField()  => (int) $entityId,
                'attribute_id' => (int) $attribute->getId(),
                'value' => $this->_prepareValueForSave($value, $attribute),
                'store_id' => (int) $storeId,
            ];

            $this->getConnection()->insert($table, $bind);
        }

        return $this;
    }
    public function getLinkField()
    {
        return 'entity_id';
    }
    public function _prepareValueForSave($value, AbstractAttribute $attribute)
    {
        $type = $attribute->getBackendType();
        if (($type == 'int' || $type == 'decimal' || $type == 'datetime') && $value === '') {
            $value = null;
        } elseif ($type == 'decimal') {
            $value = $this->_localeFormat->getNumber($value);
        }
        $backendTable = $attribute->getBackendTable();
        if (!isset(self::$_attributeBackendTables[$backendTable])) {
            self::$_attributeBackendTables[$backendTable] = $this->_connection->describeTable($backendTable);
        }
        $describe = self::$_attributeBackendTables[$backendTable];
        return $this->_connection->prepareColumnValue($describe['value'], $value);
    }
    public function _prepareTableValueForSave($value, $type)
    {
        $type = strtolower($type);
        if ($type == 'decimal' || $type == 'numeric' || $type == 'float') {
            $value = $this->_localeFormat->getNumber($value);
        }
        return $value;
    }
    public function getStoreId($storeId = 0)
    {
        $hasSingleStore = $this->_storeManager->hasSingleStore();
        $storeId = $hasSingleStore ? $this->getDefaultStoreId() : (int) $this->_storeManager->getStore($storeId)->getId();
        return $storeId;
    }
    public function getEntityType()
    {
        return $this->entityType;
    }
    private function getEntityTypeId($entityType = "")
    {
        $entityTypeEav = $entityType ? $entityType : $this->entityTypeId;
        $entityTypeId = "";
        if (!$this->entityTypeId) {
            switch ($entityTypeEav) {
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
            if ($entityType) {
                return $entityTypeId;
            }
            $this->entityTypeId = $entityTypeId;
        }
        return  $this->entityTypeId;
    }
    public function updateAttributes($productIds, $attrData, $storeId, $entityType = \Magento\Catalog\Model\Product::ENTITY)
    {
        $this->entityType = $entityType;
        $this->_eventManager->dispatch(
            'catalog_product_attribute_update_before',
            ['attributes_data' => &$attrData, 'product_ids' => &$productIds, 'store_id' => &$storeId]
        );

        $this->processUpdateAttributes($productIds, $attrData, $storeId);
        /*$this->setData(
            ['product_ids' => array_unique($productIds), 'attributes_data' => $attrData, 'store_id' => $storeId]
        );*/

        if ($this->_hasIndexableAttributes($attrData)) {
            $this->_productEavIndexerProcessor->reindexList(array_unique($productIds));
        }

        $categoryIndexer = $this->indexerRegistry->get(\Magento\Catalog\Model\Indexer\Product\Category::INDEXER_ID);
        if (!$categoryIndexer->isScheduled()) {
            $categoryIndexer->reindexList(array_unique($productIds));
        }
        return $this;
    }
    protected function _hasIndexableAttributes($attributesData)
    {
        foreach ($attributesData as $code => $value) {
            if ($this->_attributeIsIndexable($code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check is attribute indexable in EAV
     *
     * @param  \Magento\Catalog\Model\ResourceModel\Eav\Attribute|string $attribute
     * @return bool
     */
    protected function _attributeIsIndexable($attribute)
    {
        if (!$attribute instanceof \Magento\Catalog\Model\ResourceModel\Eav\Attribute) {
            $attribute = $this->_eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, $attribute);
        }

        return $attribute->isIndexable();
    }
    public function processUpdateAttributes($entityIds, $attrData, $storeId)
    {
        $object = new \Magento\Framework\DataObject();
        $object->setStoreId($storeId);

        $this->getConnection()->beginTransaction();
        try {
            foreach ($attrData as $attrCode => $value) {
                $attribute = $this->getAttribute($attrCode);
                if (!$attribute->getAttributeId()) {
                    continue;
                }

                $i = 0;
                foreach ($entityIds as $entityId) {
                    $i++;
                    $object->setId($entityId);
                    $object->setEntityId($entityId);
                    // collect data for save
                    $this->_saveAttributeValue($object, $attribute, $value);
                    // save collected data every 1000 rows
                    if ($i % 1000 == 0) {
                        $this->_processAttributeValues();
                    }
                }
                $this->_processAttributeValues();
            }
            $this->getConnection()->commit();
        } catch (\Exception $e) {
            $this->getConnection()->rollBack();
            throw $e;
        }

        return $this;
    }
    protected function getIdFieldName()
    {
        return 'entity_id';
    }
    protected function resolveEntityId($entityId)
    {
        if ($this->getIdFieldName() == $this->getLinkField()) {
            return $entityId;
        }
        $select = $this->getConnection()->select();
        $tableName = $this->_resource->getTableName('catalog_product_entity');
        $select->from($tableName, [$this->getLinkField()])
            ->where('entity_id = ?', $entityId);
        return $this->getConnection()->fetchOne($select);
    }
    protected function _processAttributeValues()
    {
        $connection = $this->getConnection();
        foreach ($this->_attributeValuesToSave as $table => $data) {
            $connection->insertOnDuplicate($table, $data, ['value']);
        }

        foreach ($this->_attributeValuesToDelete as $table => $valueIds) {
            $connection->delete($table, ['value_id IN (?)' => $valueIds]);
        }

        // reset data arrays
        $this->_attributeValuesToSave = [];
        $this->_attributeValuesToDelete = [];

        return $this;
    }
    public function getConnection()
    {
        return $this->_connection;
    }
    protected function _saveAttributeValue($object, $attribute, $value)
    {
        $connection = $this->getConnection();
        $storeId = (int) $this->_storeManager->getStore($object->getStoreId())->getId();
        $table = $attribute->getBackend()->getTable();

        $entityId = $object->getId();

        /**
         * If we work in single store mode all values should be saved just
         * for default store id
         * In this case we clear all not default values
         */
        if ($this->_storeManager->hasSingleStore()) {
            $storeId = $this->getDefaultStoreId();
            $connection->delete(
                $table,
                [
                    'attribute_id = ?' => $attribute->getAttributeId(),
                    $this->getLinkField() . ' = ?' => $entityId,
                    'store_id <> ?' => $storeId
                ]
            );
        }

        $data = new \Magento\Framework\DataObject(
            [
                'attribute_id' => $attribute->getAttributeId(),
                'store_id' => $storeId,
                $this->getLinkField() => $entityId,
                'value' => $this->_prepareValueForSave($value, $attribute),
            ]
        );
        $bind = $this->_prepareDataForTable($data, $table);

        if ($attribute->isScopeStore()) {
            /**
             * Update attribute value for store
             */
            $this->_attributeValuesToSave[$table][] = $bind;
        } elseif ($attribute->isScopeWebsite() && $storeId != $this->getDefaultStoreId()) {
            /**
             * Update attribute value for website
             */
            $storeIds = $this->_storeManager->getStore($storeId)->getWebsite()->getStoreIds(true);
            foreach ($storeIds as $storeId) {
                $bind['store_id'] = (int) $storeId;
                $this->_attributeValuesToSave[$table][] = $bind;
            }
        } else {
            /**
             * Update global attribute value
             */
            $bind['store_id'] = $this->getDefaultStoreId();
            $this->_attributeValuesToSave[$table][] = $bind;
        }

        return $this;
    }
    protected function _prepareDataForTable(DataObject $object, $table)
    {
        $data = [];
        $fields = $this->getConnection()->describeTable($table);
        foreach (array_keys($fields) as $field) {
            if ($object->hasData($field)) {
                $fieldValue = $object->getData($field);
                if ($fieldValue instanceof \Zend_Db_Expr) {
                    $data[$field] = $fieldValue;
                } else {
                    if (null !== $fieldValue) {
                        $fieldValue = $this->_prepareTableValueForSave($fieldValue, $fields[$field]['DATA_TYPE']);
                        $data[$field] = $this->getConnection()->prepareColumnValue($fields[$field], $fieldValue);
                    } elseif (!empty($fields[$field]['NULLABLE'])) {
                        $data[$field] = null;
                    }
                }
            }
        }
        return $data;
    }
    public function updateCategoryAttributes($categoryIds, $attrData, $storeId = 0)
    {
        $this->updateAttributes($categoryIds, $attrData, $storeId, \Magento\Catalog\Model\Category::ENTITY);
    }
    public function updateProductAttributes($productIds, $attrData, $storeId = 0)
    {
        $this->updateAttributes($productIds, $attrData, $storeId);
    }
    public function updateCustomerAttributes($customerIds, $attrData, $storeId = 0)
    {
        $this->updateAttributes($customerIds, $attrData, $storeId, \Magento\Customer\Model\Customer::ENTITY);
    }
    public function updateCustomerAddressAttributes($customerAddressIds, $attrData, $storeId = 0)
    {
        $this->updateAttributes($customerAddressIds, $attrData, $storeId, 'customer_address');
    }
}