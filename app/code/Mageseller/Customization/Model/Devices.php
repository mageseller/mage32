<?php
/**
 * Mageseller
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageseller.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageseller.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageseller
 * @package   Mageseller_Customization
 * @copyright Copyright (c) 2017 Mageseller (http://www.mageseller.com/)
 * @license   https://www.mageseller.com/LICENSE.txt
 */
namespace Mageseller\Customization\Model;

use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory;
use Mageseller\Customization\Helper\Data as Helper;

/**
 * Class Devices
 *
 * @package Mageseller\Customization\Model
 */
class Devices extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Cache tag
     *
     * @var string
     */
    const CACHE_TAG = 'mageseller_custom_attribute_information';

    /**
     * Cache tag
     *
     * @var string
     */
    protected $_cacheTag = 'mageseller_custom_attribute_information';

    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'mageseller_custom_attribute_information';

    /**
     * @type \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @type \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @type \Mageseller\Customization\Helper\Data
     */
    protected $helper;

    /**
     * @type \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory
     */
    protected $_attrOptionCollectionFactory;

    /**
     * Devices constructor.
     *
     * @param \Magento\Framework\Model\Context                                           $context
     * @param \Magento\Framework\Registry                                                $registry
     * @param \Magento\Eav\Model\Config                                                  $eavConfig
     * @param \Mageseller\Customization\Helper\Data                                      $helper
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface                                 $storeManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null               $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null                         $resourceCollection
     * @param array                                                                      $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        Config $eavConfig,
        Helper $helper,
        CollectionFactory $attrOptionCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->eavConfig                    = $eavConfig;
        $this->helper                       = $helper;
        $this->_storeManager                = $storeManager;
        $this->_attrOptionCollectionFactory = $attrOptionCollectionFactory;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mageseller\Customization\Model\ResourceModel\Devices');
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @param  null  $storeId
     * @param  array $conditions
     * @return mixed
     */
    public function getDevicesCollection($storeId = null, $conditions = [], $sqlString = null)
    {
        $storeId    = is_null($storeId) ? $this->_storeManager->getStore()->getId() : $storeId;
        $collection = $this->_attrOptionCollectionFactory->create()
            ->setPositionOrder('asc')
            ->setStoreFilter($storeId);

        $connection       = $collection->getConnection();
        $storeIdCondition = 0;
        if ($storeId) {
            $storeIdCondition = $connection->select()
                ->from(['ab' => $collection->getTable('mageseller_custom_attribute_information')], 'MAX(ab.store_id)')
                ->where('ab.option_id = br.option_id AND ab.store_id IN (0, ' . $storeId . ')');
        }

        $collection->getSelect()
            ->joinLeft(
                ['br' => $collection->getTable('mageseller_custom_attribute_information')],
                "main_table.option_id = br.option_id AND br.store_id = (" . $storeIdCondition . ')' . (is_string($conditions) ? $conditions : ''),
                [
                    'customization_id' => new \Zend_Db_Expr($connection->getCheckSql('br.store_id = ' . $storeId, 'br.customization_id', 'NULL')),
                    'store_id' => new \Zend_Db_Expr($storeId),
                    'alternate_options'
                ]
            )
            ->joinLeft(
                ['sw' => $collection->getTable('eav_attribute_option_swatch')],
                "main_table.option_id = sw.option_id",
                ['swatch_type' => 'type', 'swatch_value' => 'value']
            )
            ->group('option_id');

        if (is_array($conditions)) {
            foreach ($conditions as $field => $condition) {
                $collection->addFieldToFilter($field, $condition);
            }
        }
        if ($sqlString) {
            $collection->getSelect()->where($sqlString);
        }
        return $collection;
    }

    /**
     * @param  $optionId
     * @param  null     $store
     * @return mixed
     */
    public function loadByOption($optionId, $store = null)
    {
        $collection = $this->getDevicesCollection($store, ['main_table.option_id' => $optionId]);

        return $collection->getFirstItem();
    }
}
