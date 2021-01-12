<?php

namespace Mageseller\SupplierImport\Controller\XitCategory;

class DelCategory extends \Magento\Framework\App\Action\Action
{
    /** @var  \Magento\Framework\View\Result\Page */
    protected $resultPageFactory;
    protected $helper;
    protected $_registry;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**      *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Mageseller\SupplierImport\Helper\Xit $helper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Mageseller\SupplierImport\Helper\Xit $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
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
        if ($postData = $this->getRequest()->getParams()) {
            $shopId = $postData['shopId'];
        }
        $category = $this->helper->getCategoryById($shopId);
        if ($category) {
            $storeId = $this->storeManager->getStore()->getStoreId();
            $category->setStoreId($storeId)->setMapCategory(null)->save();
            $category->setStoreId(0)->setMapCategory(null)->save();
            echo $this->helper->getCategories(1);
        }
    }
}
