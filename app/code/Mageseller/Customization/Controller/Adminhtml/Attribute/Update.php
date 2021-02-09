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
namespace Mageseller\Customization\Controller\Adminhtml\Attribute;

/**
 * Class Update
 *
 * @package Mageseller\Customization\Controller\Adminhtml\Attribute
 */
class Update extends \Magento\Backend\App\Action
{
    /**
     * @type \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * @type \Mageseller\Customization\Helper\Data
     */
    protected $_devicesHelper;

    /**
     * @type \Magento\Catalog\Model\Product\Attribute\Repository
     */
    protected $_productAttributeRepository;

    /**
     * @type \Mageseller\Customization\Model\DevicesFactory
     */
    protected $_devicesFactory;

    /**
     * @type \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @type \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Backend\App\Action\Context                 $context
     * @param \Magento\Framework\Json\Helper\Data                 $jsonHelper
     * @param \Magento\Store\Model\StoreManagerInterface          $storeManager
     * @param \Magento\Catalog\Model\Product\Attribute\Repository $productRespository
     * @param \Magento\Framework\View\Result\PageFactory          $resultPageFactory
     * @param \Mageseller\Customization\Helper\Data               $devicesHelper
     * @param \Mageseller\Customization\Model\DevicesFactory      $devicesFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product\Attribute\Repository $productRespository,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Mageseller\Customization\Helper\Data $devicesHelper,
        \Mageseller\Customization\Model\DevicesFactory $devicesFactory
    ) {
        parent::__construct($context);

        $this->_jsonHelper                 = $jsonHelper;
        $this->_devicesHelper                = $devicesHelper;
        $this->_productAttributeRepository = $productRespository;
        $this->_devicesFactory               = $devicesFactory;
        $this->_resultPageFactory          = $resultPageFactory;
        $this->_storeManager               = $storeManager;
    }

    /**
     * execute
     */
    public function execute()
    {
        $result        = ['success' => false];
        $optionId      = (int)$this->getRequest()->getParam('id');
        /*$attributeCode = $this->_devicesHelper->getAttributeCode();
        $options       = $this->_productAttributeRepository->get($attributeCode)->getOptions();
        foreach ($options as $option) {
            if ($option->getValue() == $optionId) {
                $result = ['success' => true];
                break;
            }
        }*/
        $result = ['success' => true];

        if (!$result['success']) {
            $result['message'] = __('Attribute option does not exist.');
        } else {
            $store = $this->getRequest()->getParam('store', 0);
            $devices = $this->_devicesFactory->create()->loadByOption($optionId, $store);
            /**
 * @var \Magento\Backend\Model\View\Result\Page $resultPage 
*/
            $resultPage     = $this->_resultPageFactory->create();
            $devicesData = $devices->getData();
            if(!$devicesData) {
                $devicesData['option_id'] = $optionId;
                $devicesData['store_id'] = $store;
            }
            $result['html'] = $resultPage->getLayout()->getBlock('devices.attribute.html')
                ->setOptionData($devicesData)
                ->toHtml();
            $result['switcher'] = $resultPage->getLayout()->getBlock('devices.store.switcher')
                ->toHtml();
        }

        $this->getResponse()->representJson($this->_jsonHelper->jsonEncode($result));
    }
}
