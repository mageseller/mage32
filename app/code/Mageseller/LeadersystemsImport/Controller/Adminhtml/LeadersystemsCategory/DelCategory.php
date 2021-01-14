<?php

namespace Mageseller\LeadersystemsImport\Controller\Adminhtml\LeadersystemsCategory;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mageseller\LeadersystemsImport\Helper\EavAtrributeUpdateHelper;
use Mageseller\LeadersystemsImport\Helper\EavAttributeDataWithoutLoad;
use Mageseller\LeadersystemsImport\Helper\Leadersystems;

class DelCategory extends Action
{
    /** @var  Page */
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

    /**      *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Leadersystems $helper
     * @param EavAtrributeUpdateHelper $eavAtrributeUpdateHelper
     * @param EavAttributeDataWithoutLoad $attributeDataWithoutLoad
     * @param array $data
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Leadersystems $helper,
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
                $leadersystemsCategoryIds = $this->attributeDataWithoutLoad->getCategoryAttributeRawValue($shopId, ['leadersystems_category_ids' ]);
                if ($leadersystemsCategoryIds) {
                    $leadersystemsCategoryIds = explode(",", $leadersystemsCategoryIds);
                    $leadersystemsCategoryIds = array_diff($leadersystemsCategoryIds, [$supplierId]);
                }
                if ($leadersystemsCategoryIds) {
                    $this->eavAtrributeUpdateHelper->updateCategoryAttributes([$shopId], ['leadersystems_category_ids' => implode(",", $leadersystemsCategoryIds)]);
                } else {
                    $this->eavAtrributeUpdateHelper->updateCategoryAttributes([$shopId], ['leadersystems_category_ids' => null]);
                }
            }
        }
        $result = $this->resultFactory->create($this->resultFactory::TYPE_RAW);
        $result->setContents($this->helper->getCategories(1));
        return $result;
    }
}
