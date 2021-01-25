<?php
/**
 * A Magento 2 module named Mageseller/IngrammicroImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/IngrammicroImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\IngrammicroImport\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();
        $table_mageseller_ingrammicroimport_ingrammicrocategory = $installer->getConnection()->newTable($setup->getTable('mageseller_ingrammicroimport_ingrammicrocategory'));

        $table_mageseller_ingrammicroimport_ingrammicrocategory->addColumn(
            'ingrammicrocategory_id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
            'Entity ID'
        )->addColumn(
            'name',
            Table::TYPE_TEXT,
            null,
            [],
            'name'
        )->addColumn(
            'magento_cat_id',
            Table::TYPE_INTEGER,
            null,
            [],
            'Magento Category Id'
        )->addColumn(
            'supplier_cat_id',
            Table::TYPE_TEXT,
            null,
            [],
            'Supplier Category Id'
        )->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
            'Created At'
        )->addColumn(
            'updated_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
            'Updated At'
        )->setComment('Ingrammicro Supplier Category');

        $indexName = $setup->getIdxName(
            $setup->getTable('mageseller_ingrammicroimport_ingrammicrocategory'),
            [ 'name','ingrammicrocategory_id'],
            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
        );
        $installer->getConnection()->createTable($table_mageseller_ingrammicroimport_ingrammicrocategory);
        $installer->getConnection()->rawQuery("ALTER TABLE `{$setup->getTable('mageseller_ingrammicroimport_ingrammicrocategory')}` ADD UNIQUE `{$indexName}` (`supplier_cat_id`(255),`ingrammicrocategory_id`)");
    }

}
