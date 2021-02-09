<?php

namespace Mageseller\XitImport\Controller\Adminhtml\XitCategory;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mageseller\XitImport\Helper\EavAtrributeUpdateHelper;
use Mageseller\XitImport\Helper\EavAttributeDataWithoutLoad;
use Mageseller\XitImport\Helper\Xit;

class DelCategory extends Action
{
    /**
     * @var Page 
     */
    protected $resultPageFactory;
    protected $helper;
    protected $_registry;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var EavAttributeDataWithoutLoad
     */
    private $attributeDataWithoutLoad;
    /**
     * @var EavAtrributeUpdateHelper
     */
    private $eavAtrributeUpdateHelper;

    /**
     * *
     *
     * @param Context                     $context
     * @param PageFactory                 $resultPageFactory
     * @param Xit                         $helper
     * @param EavAtrributeUpdateHelper    $eavAtrributeUpdateHelper
     * @param EavAttributeDataWithoutLoad $attributeDataWithoutLoad
     * @param array                       $data
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Xit $helper,
        EavAtrributeUpdateHelper $eavAtrributeUpdateHelper,
        EavAttributeDataWithoutLoad $attributeDataWithoutLoad,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->eavAtrributeUpdateHelper = $eavAtrributeUpdateHelper;
        $this->attributeDataWithoutLoad = $attributeDataWithoutLoad;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Blog Index, shows a list of recent blog posts.
     *
     * @return PageFactory
     */
    public function execute()
    {
        if ($postData = $this->getRequest()->getParams()) {
            $shopId = $postData['shopId'] ?? "";
            $supplierId = $postData['supplierId'] ?? "";
            if ($shopId) {
                $xitCategoryIds = $this->attributeDataWithoutLoad->getCategoryAttributeRawValue($shopId, ['xit_category_ids' ]);
                if ($xitCategoryIds) {
                    $xitCategoryIds = explode(",", $xitCategoryIds);
                    $xitCategoryIds = array_diff($xitCategoryIds, [$supplierId]);
                }
                if ($xitCategoryIds) {
                    $this->eavAtrributeUpdateHelper->updateCategoryAttributes([$shopId], ['xit_category_ids' => implode(",", $xitCategoryIds)]);
                } else {
                    $this->eavAtrributeUpdateHelper->updateCategoryAttributes([$shopId], ['xit_category_ids' => null]);
                }
            }
        }
        $result = $this->resultFactory->create($this->resultFactory::TYPE_RAW);
        $result->setContents($this->helper->getCategories(1));
        return $result;
    }
}
