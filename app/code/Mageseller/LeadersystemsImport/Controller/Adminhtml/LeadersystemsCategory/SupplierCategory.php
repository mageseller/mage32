<?php
namespace Mageseller\LeadersystemsImport\Controller\Adminhtml\LeadersystemsCategory;

 use Magento\Backend\App\Action;
 use Magento\Backend\App\Action\Context;
 use Magento\Framework\View\Result\Page;
 use Magento\Framework\View\Result\PageFactory;
 use Mageseller\LeadersystemsImport\Helper\Leadersystems;

 class SupplierCategory extends Action
 {
     /** @var  Page */
     protected $resultPageFactory;
     protected $helper;

     /**      *
      * @param Context $context
      * @param PageFactory $resultPageFactory
      * @param Leadersystems $helper
      * @param array $data
      */
     public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Leadersystems $helper,
        array $data = []
    ) {
         $this->helper = $helper;
         $this->resultPageFactory = $resultPageFactory;
         parent::__construct($context);
     }

     public function execute()
     {
         $result = $this->resultFactory->create($this->resultFactory::TYPE_RAW);
         $result->setContents($this->helper->getSupplierCategoryData());
         return $result;
     }
 }
