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

namespace Mageseller\Customization\Setup;

/**
 * Class InstallSchema
 *
 * @package Mageseller\Customization\Setup
 */
class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * install tables
     *
     * @param                                         \Magento\Framework\Setup\SchemaSetupInterface   $setup
     * @param                                         \Magento\Framework\Setup\ModuleContextInterface $context
     * @return                                        void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        if (!$installer->tableExists('mageseller_custom_attribute_information')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('mageseller_custom_attribute_information'))
                ->addColumn(
                    'customization_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary' => true,
                        'unsigned' => true,
                    ],
                    'Customization ID'
                )
                ->addColumn('option_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['unsigned' => true, 'nullable' => false], 'Attribute Option Id')
                ->addColumn('store_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['nullable' => false, 'default' => '0'], 'Config Scope Id')
                ->addColumn('alternate_options', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, [], 'Replacement Keywords')
                ->addForeignKey(
                    $installer->getFkName('mageseller_custom_attribute_information', 'option_id', 'eav_attribute_option', 'option_id'),
                    'option_id',
                    $installer->getTable('eav_attribute_option'),
                    'option_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->addIndex(
                    $installer->getIdxName('mageseller_custom_attribute_information', ['option_id', 'store_id'], true),
                    ['option_id', 'store_id'],
                    ['type' => 'unique']
                )
                ->setComment('Custom Attribute Option Table');

            $installer->getConnection()->createTable($table);
        }
        $installer->endSetup();
    }
}
