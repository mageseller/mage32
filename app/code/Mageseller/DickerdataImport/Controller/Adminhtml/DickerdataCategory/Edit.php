<?php
/**
 * A Magento 2 module named Mageseller/DickerdataImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/DickerdataImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\DickerdataImport\Controller\Adminhtml\DickerdataCategory;

class Edit extends \Mageseller\DickerdataImport\Controller\Adminhtml\DickerdataCategory
{

    protected $resultPageFactory;

    /**
     * @param \Magento\Backend\App\Action\Context        $context
     * @param \Magento\Framework\Registry                $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * Edit action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('dickerdatacategory_id');
        $model = $this->_objectManager->create(\Mageseller\DickerdataImport\Model\DickerdataCategory::class);
        
        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This Dickerdatacategory no longer exists.'));
                /**
 * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect 
*/
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $this->_coreRegistry->register('mageseller_dickerdataimport_dickerdatacategory', $model);
        
        // 3. Build edit form
        /**
 * @var \Magento\Backend\Model\View\Result\Page $resultPage 
*/
        $resultPage = $this->resultPageFactory->create();
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Dickerdatacategory') : __('New Dickerdatacategory'),
            $id ? __('Edit Dickerdatacategory') : __('New Dickerdatacategory')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Dickerdatacategorys'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? __('Edit Dickerdatacategory %1', $model->getId()) : __('New Dickerdatacategory'));
        return $resultPage;
    }
}
