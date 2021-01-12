<?php
namespace Mageseller\SupplierImport\Controller\XitCategory;

 class SupplierCategory extends \Magento\Framework\App\Action\Action
 {
     /** @var  \Magento\Framework\View\Result\Page */
     protected $resultPageFactory;
     protected $helper;

     /**      *
      * @param \Magento\Framework\App\Action\Context $context
      * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
      * @param \Mageseller\SupplierImport\Helper\Xit $helper
      * @param array $data
      */
     public function __construct(
         \Magento\Framework\App\Action\Context $context,
         \Magento\Framework\View\Result\PageFactory $resultPageFactory,
         \Mageseller\SupplierImport\Helper\Xit $helper,
         array $data = []
     ) {
         $this->helper = $helper;
         $this->resultPageFactory = $resultPageFactory;
         parent::__construct($context);
     }

     /**
      * Blog Index, shows a list of recent blog posts.
      *
      * @return \Magento\Framework\View\Result\PageFactory
      */
     public function execute()
     {
         echo $this->helper->getSupplierCategoryData();
     }
 }
