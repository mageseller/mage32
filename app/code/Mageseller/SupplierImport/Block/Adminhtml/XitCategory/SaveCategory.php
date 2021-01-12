<?php

namespace Mageseller\SupplierImport\Controller\XitCategory;

class SaveCategory extends \Magento\Framework\App\Action\Action
{
    /** @var  \Magento\Framework\View\Result\Page */
    protected $resultPageFactory;
    protected $helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    private $categoryCollectionFactory;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $categoryFactory;

    /**      *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Mageseller\SupplierImport\Helper\Xit $helper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Mageseller\SupplierImport\Helper\Xit $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryFactory = $categoryFactory;
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
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $categories = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('*');
        $categoryNameArray = [];
        foreach ($categories as $category) {
            $categoryNameArray[] = $category->getName();
        }

        if ($postData = $this->getRequest()->getParams()) {
            $supplier = $postData['supplier'];
            $shop = $postData['shop'];
            $shopId = $postData['shopId'];
            $sortorder = $postData['sortorder'];
        }
        $storeId = $this->storeManager->getStore()->getStoreId();
        $name = ucfirst($supplier);

        $categories = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('*');
        $categoryNameArray = [];

        foreach ($categories as $category) {
            $mappedCategories = explode(",", $category->getData('xit_category_ids'));
            foreach ($mappedCategories as $mapCategory) {
                $categoryNameArray[] = $mapCategory;
            }
        }
        if (in_array($name, $categoryNameArray)) {
            $this->messageManager->addSuccess(__('Thanks for your valuable feedback.'));
        } else {
            $categoryMap = $this->categoryFactory->create()->load($shopId)->getData('xit_category_ids');
            if ($categoryMap) {
                $name = $categoryMap . "," . $name;
            }
            $cat_info = $this->categoryFactory->create()
                                                ->load($shopId)
                                                ->setStoreId($storeId)
                                                ->setData('xit_category_ids', $name)
                                                ->save();
            $cat_info->setStoreId(0)->setData('xit_category_ids', $name)->save();
        }

        echo $this->helper->getCategories(1);
    }
}
