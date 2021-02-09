<?php

namespace Mageseller\IngrammicroImport\Model\Config\Backend;

use Magento\Framework\Model\AbstractModel;

/**
 * Backend model for shipping table rates CSV importing
 *
 * @author Magento Core Team <core@magentocommerce.com>
 */
class ImportCategory extends \Magento\Framework\App\Config\Value
{
    /**
     * @var \Mageseller\IngrammicroImport\Helper\Ingrammicro
     */
    private $ingrammicroHelper;

    /**
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $config
     * @param \Magento\Framework\App\Cache\TypeListInterface               $cacheTypeList
     * @param \Mageseller\IngrammicroImport\Helper\Ingrammicro             $ingrammicroHelper
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Mageseller\IngrammicroImport\Helper\Ingrammicro $ingrammicroHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->ingrammicroHelper = $ingrammicroHelper;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return $this
     */
    public function afterSave()
    {
        $this->ingrammicroHelper->importIngrammicroCategory();
        return parent::afterSave();
    }
}
