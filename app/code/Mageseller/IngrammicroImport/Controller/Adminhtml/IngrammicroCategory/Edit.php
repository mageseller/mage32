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

namespace Mageseller\IngrammicroImport\Controller\Adminhtml\IngrammicroCategory;

class Edit extends \Mageseller\IngrammicroImport\Controller\Adminhtml\IngrammicroCategory
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
        $id = $this->getRequest()->getParam('ingrammicrocategory_id');
        $model = $this->_objectManager->create(\Mageseller\IngrammicroImport\Model\IngrammicroCategory::class);
        
        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This Ingrammicrocategory no longer exists.'));
                /**
 * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect 
*/
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $this->_coreRegistry->register('mageseller_ingrammicroimport_ingrammicrocategory', $model);
        
        // 3. Build edit form
        /**
 * @var \Magento\Backend\Model\View\Result\Page $resultPage 
*/
        $resultPage = $this->resultPageFactory->create();
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Ingrammicrocategory') : __('New Ingrammicrocategory'),
            $id ? __('Edit Ingrammicrocategory') : __('New Ingrammicrocategory')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Ingrammicrocategorys'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? __('Edit Ingrammicrocategory %1', $model->getId()) : __('New Ingrammicrocategory'));
        return $resultPage;
    }
}
