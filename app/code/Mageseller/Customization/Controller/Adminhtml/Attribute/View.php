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
namespace Mageseller\Customization\Controller\Adminhtml\Attribute;

/**
 * Class View
 * @package Mageseller\Customization\Controller\Adminhtml\Attribute
 */
class View extends \Magento\Backend\App\Action
{
    /**
     * @type \Mageseller\Customization\Helper\Data
     */
    protected $_devicesHelper;

    /**
     * @type \Magento\Catalog\Model\Product\Attribute\Repository
     */
    protected $_productAttributeRepository;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Catalog\Model\Product\Attribute\Repository $productRespository
     * @param \Mageseller\Customization\Helper\Data $devicesHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Catalog\Model\Product\Attribute\Repository $productRespository,
        \Mageseller\Customization\Helper\Data $devicesHelper
    ) {
        parent::__construct($context);

        $this->_devicesHelper                = $devicesHelper;
        $this->_productAttributeRepository = $productRespository;
    }

    /**
     * execute
     */
    public function execute()
    {
        $attributeCode = $this->_devicesHelper->getAttributeCode('0');
        try {
            $attribute = $this->_productAttributeRepository->get($attributeCode);

            $this->_forward('edit', 'product_attribute', 'catalog', ['attribute_id' => $attribute->getId()]);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('You have to choose an attribute as devices in configuration.'));
            $this->_redirect('adminhtml/system_config/edit', ['section' => 'shopbydevices']);
        }
    }
}
