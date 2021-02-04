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
 * @category    Mageseller
 * @package     Mageseller_Customization
 * @copyright   Copyright (c) 2017 Mageseller (http://www.mageseller.com/)
 * @license     https://www.mageseller.com/LICENSE.txt
 */
namespace Mageseller\Customization\Model\ResourceModel\Devices;

/**
 * Class Collection
 * @package Mageseller\Customization\Model\ResourceModel\Devices
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * ID Field Name
     *
     * @var string
     */
    protected $_idFieldName = 'customization_id';

    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'mageseller_customization_devices_collection';

    /**
     * Event object
     *
     * @var string
     */
    protected $_eventObject = 'devices_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mageseller\Customization\Model\Devices', 'Mageseller\Customization\Model\ResourceModel\Devices');
    }

    /**
     * Get SQL for get record count.
     * Extra GROUP BY strip added.
     *
     * @return \Magento\Framework\DB\Select
     */
    public function getSelectCountSql()
    {
        $countSelect = parent::getSelectCountSql();
        $countSelect->reset(\Zend_Db_Select::GROUP);

        return $countSelect;
    }

    /**
     * @param string $valueField
     * @param string $labelField
     * @param array $additional
     * @return array
     */
    protected function _toOptionArray($valueField = 'customization_id', $labelField = 'name', $additional = [])
    {
        return parent::_toOptionArray($valueField, $labelField, $additional);
    }
}
