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

namespace Mageseller\XitImport\Model\ResourceModel\XitCategory;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Mageseller\XitImport\Model\XitCategory::class,
            \Mageseller\XitImport\Model\ResourceModel\XitCategory::class
        );
    }
    /**
     * Insert multiple
     *
     * @param  array $data
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Exception
     */
    public function insertMultiple($data)
    {
        try {
            return $this->connection->insertMultiple($this->getMainTable(), $data);
        } catch (\Exception $e) {
            if ($e->getCode() === self::ERROR_CODE_DUPLICATE_ENTRY
                && preg_match('#SQLSTATE\[23000\]: [^:]+: 1062[^\d]#', $e->getMessage())
            ) {
                throw new \Magento\Framework\Exception\AlreadyExistsException(
                    __('URL key for specified store already exists.')
                );
            }
            throw $e;
        }
    }

    /**
     * Insert On Duplicate
     *
     * @param  $tableName
     * @param  array     $data
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function insertOnDuplicate($data)
    {
        try {
            return $this->getConnection()->insertOnDuplicate($this->getMainTable(), $data);
        } catch (\Exception $e) {
            if ($e->getCode() === self::ERROR_CODE_DUPLICATE_ENTRY
                && preg_match('#SQLSTATE\[23000\]: [^:]+: 1062[^\d]#', $e->getMessage())
            ) {
                throw new \Magento\Framework\Exception\AlreadyExistsException(
                    __('URL key for specified store already exists.')
                );
            }
            throw $e;
        }
    }
}
