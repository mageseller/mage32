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

use Magento\Framework\App\Filesystem\DirectoryList;
use Mageseller\Customization\Helper\Data as DevicesHelper;

/**
 * Class Save
 *
 * @package Mageseller\Customization\Controller\Adminhtml\Attribute
 */
class Save extends \Magento\Backend\App\Action
{
    /**
     * @type \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * @type \Mageseller\Customization\Model\DevicesFactory
     */
    protected $_devicesFactory;

    /**
     * @type \Magento\Framework\Filesystem
     */
    protected $_fileSystem;

    /**
     * @type \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @param \Magento\Backend\App\Action\Context            $context
     * @param \Mageseller\Customization\Helper\Data          $devicesHelper
     * @param \Magento\Framework\Json\Helper\Data            $jsonHelper
     * @param \Mageseller\Customization\Model\DevicesFactory $devicesFactory
     * @param \Magento\Framework\Filesystem                  $fileSystem
     * @param \Magento\Framework\View\Result\PageFactory     $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Mageseller\Customization\Helper\Data $devicesHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Mageseller\Customization\Model\DevicesFactory $devicesFactory,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);

        $this->_devicesHelper       = $devicesHelper;
        $this->_jsonHelper        = $jsonHelper;
        $this->_devicesFactory      = $devicesFactory;
        $this->_fileSystem        = $fileSystem;
        $this->_resultPageFactory = $resultPageFactory;
    }

    /**
     * execute
     */
    public function execute()
    {
        $result = ['success' => true];
        $data   = $this->getRequest()->getPostValue();
        if ($result['success']) {
            try {
                $devices = $this->_devicesFactory->create();

                if ($data['store_id'] != \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
                    $defaultDevices = $devices->loadByOption($data['option_id'], \Magento\Store\Model\Store::DEFAULT_STORE_ID);
                    if (!$defaultDevices->getDevicesId()) {
                        $devices->setData($data)
                            ->setId(null)
                            ->setStoreId(0)
                            ->save();
                    }
                }

                $devices->setData($data)
                    ->setId($this->getRequest()->getParam('id'))
                    ->save();

                $resultPage     = $this->_resultPageFactory->create();
                $result['html'] = $resultPage->getLayout()->getBlock('devices.attribute.html')
                    ->setOptionData($devices->getData())
                    ->toHtml();

                $result['message'] = __('Devices option has been saved successfully.');
            } catch (\Exception $e) {
                $result['success'] = false;
                $result['message'] = $e->getMessage();//__('An error occur. Please try again later.');
            }
        }

        $this->getResponse()->representJson($this->_jsonHelper->jsonEncode($result));
    }

    /**
     * @param  $data
     * @param  $result
     * @return $this
     */
    protected function _uploadImage(&$data, &$result)
    {
        if (isset($data['image']) && isset($data['image']['delete']) && $data['image']['delete']) {
            $data['image'] = '';
        } else {
            try {
                $uploader = $this->_objectManager->create(
                    'Magento\MediaStorage\Model\File\Uploader',
                    ['fileId' => 'image']
                );
                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
                $uploader->setAllowRenameFiles(true);

                $image = $uploader->save(
                    $this->_fileSystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath(DevicesHelper::BRAND_MEDIA_PATH)
                );

                $data['image'] = DevicesHelper::BRAND_MEDIA_PATH . '/' . $image['file'];
            } catch (\Exception $e) {
                $data['image'] = isset($data['image']['value']) ? $data['image']['value'] : '';
                if ($e->getCode() != \Magento\Framework\File\Uploader::TMP_NAME_EMPTY) {
                    $result['success'] = false;
                    $result['message'] = $e->getMessage();
                }
            }
        }

        return $this;
    }
}
