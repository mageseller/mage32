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
namespace Mageseller\Customization\Block\Adminhtml\Attribute\Edit;

/**
 * Class Options
 * @package Mageseller\Customization\Block\Adminhtml\Attribute\Edit
 */
class Options extends \Magento\Eav\Block\Adminhtml\Attribute\Edit\Options\Options
{
    /** @var string Option template */
    protected $_template = 'Mageseller_Customization::catalog/product/attribute/options.phtml';
    /**
     * @var \Mageplaza\Shopbybrand\Helper\Data|\Mageseller\Customization\Helper\Data
     */
    private $brandHelper;
    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Options constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory
     * @param \Magento\Framework\Validator\UniversalFactory $universalFactory
     * @param \Mageplaza\Shopbybrand\Helper\Data $helper
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        parent::__construct($context, $registry, $attrOptionCollectionFactory, $universalFactory, $data);

        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
        if (version_compare($productMetadata->getVersion(), '2.1.0') < 0) {
            $this->setTemplate('Mageseller_Customization::catalog/product/attribute/options_old.phtml');
        }
    }

    /**
     * @return bool
     */
    public function isSelectAttribute()
    {
        return $this->getAttributeObject()->getFrontendInput() == "select";
    }
    /**
     * @return bool
     */
    public function isBrandAttribute()
    {
        if ($this->moduleManager->isEnabled('Mageplaza_Shopbybrand')) {
            $this->brandHelper = $this->objectManager->get(\Mageplaza\Shopbybrand\Helper\Data::class);
            return $this->brandHelper->isEnabled() && (in_array($this->getAttributeObject()->getAttributeCode(), $this->brandHelper->getAllBrandsAttributeCode()));
        }
        return  false;
    }

    /**
     * @return string
     */
    public function getDevicesUpdateUrl()
    {
        return $this->getUrl('devices/attribute/update');
    }

    /**
     * Returns stores sorted by Sort Order
     *
     * @return array
     */
    public function getStoresSortedBySortOrder()
    {
        $stores = $this->getStores();
        if (is_array($stores)) {
            usort($stores, function ($storeA, $storeB) {
                if ($storeA->getSortOrder() == $storeB->getSortOrder()) {
                    return $storeA->getId() < $storeB->getId() ? -1 : 1;
                }

                return ($storeA->getSortOrder() < $storeB->getSortOrder()) ? -1 : 1;
            });
        }

        return $stores;
    }
}
