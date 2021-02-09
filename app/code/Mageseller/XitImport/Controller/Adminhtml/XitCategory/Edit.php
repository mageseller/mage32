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

namespace Mageseller\XitImport\Controller\Adminhtml\XitCategory;

class Edit extends \Mageseller\XitImport\Controller\Adminhtml\XitCategory
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
        $id = $this->getRequest()->getParam('xitcategory_id');
        $model = $this->_objectManager->create(\Mageseller\XitImport\Model\XitCategory::class);
        
        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This Xitcategory no longer exists.'));
                /**
 * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect 
*/
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $this->_coreRegistry->register('mageseller_xitimport_xitcategory', $model);
        
        // 3. Build edit form
        /**
 * @var \Magento\Backend\Model\View\Result\Page $resultPage 
*/
        $resultPage = $this->resultPageFactory->create();
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Xitcategory') : __('New Xitcategory'),
            $id ? __('Edit Xitcategory') : __('New Xitcategory')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Xitcategorys'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? __('Edit Xitcategory %1', $model->getId()) : __('New Xitcategory'));
        return $resultPage;
    }
}
