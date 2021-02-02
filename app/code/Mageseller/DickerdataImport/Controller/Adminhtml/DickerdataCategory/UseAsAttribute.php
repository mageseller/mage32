<?php
namespace Mageseller\DickerdataImport\Controller\Adminhtml\DickerdataCategory;

 use Magento\Backend\App\Action;
 use Magento\Backend\App\Action\Context;
 use Magento\Framework\View\Result\Page;
 use Magento\Framework\View\Result\PageFactory;
 use Mageseller\DickerdataImport\Helper\Dickerdata;
 use Mageseller\DickerdataImport\Model\DickerdataCategoryFactory;

 class UseAsAttribute extends Action
 {
     /** @var  Page */
     protected $resultPageFactory;
     protected $helper;
     /**
      * @var DickerdataCategoryFactory
      */
     private $dickerdataCategoryFactory;

     /**      *
      * @param Context $context
      * @param PageFactory $resultPageFactory
      * @param DickerdataCategoryFactory $dickerdataCategoryFactory
      * @param Dickerdata $helper
      * @param array $data
      */
     public function __construct(
         Context $context,
         PageFactory $resultPageFactory,
         DickerdataCategoryFactory $dickerdataCategoryFactory,
         Dickerdata $helper,
         array $data = []
     ) {
         $this->helper = $helper;
         $this->resultPageFactory = $resultPageFactory;
         $this->dickerdataCategoryFactory = $dickerdataCategoryFactory;
         parent::__construct($context);
     }

     public function execute()
     {
         $result = $this->resultFactory->create($this->resultFactory::TYPE_RAW);
         $categoryId = $this->getRequest()->getParam('category_id');
         $attributeId = $this->getRequest()->getParam('attribute_id');
         $remove = $this->getRequest()->getParam('remove_attribute');
         if ($categoryId && $attributeId) {
             $dickerCategory = $this->dickerdataCategoryFactory->create()->load($categoryId);
             $dickerCategory->setData('attribute_id', $attributeId)->save();
             $result->setContents($this->helper->getSupplierCategoryData());
         }
         if ($categoryId && $remove) {
             $dickerCategory = $this->dickerdataCategoryFactory->create()->load($categoryId);
             $dickerCategory->setData('attribute_id', null)->save();
             $result->setContents($this->helper->getSupplierCategoryData());
         }
         return $result;
     }
 }
