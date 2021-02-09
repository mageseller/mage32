<?php

namespace Mageseller\LeadersystemsImport\Controller\Adminhtml\LeadersystemsCategory;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Mageseller\LeadersystemsImport\Helper\EavAtrributeUpdateHelper;
use Mageseller\LeadersystemsImport\Helper\EavAttributeDataWithoutLoad;
use Mageseller\LeadersystemsImport\Helper\Leadersystems;

class SaveCategory extends Action
{
    /**
     * @var Page 
     */
    protected $resultPageFactory;
    protected $helper;
    /**
     * @var EavAtrributeUpdateHelper
     */
    protected $eavAtrributeUpdateHelper;
    /**
     * @var EavAttributeDataWithoutLoad
     */
    private $attributeDataWithoutLoad;

    /**
     * *
     *
     * @param Context                     $context
     * @param PageFactory                 $resultPageFactory
     * @param Leadersystems               $helper
     * @param EavAtrributeUpdateHelper    $eavAtrributeUpdateHelper
     * @param EavAttributeDataWithoutLoad $attributeDataWithoutLoad
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
        $this->resultPageFactory = $resultPageFactory;
        $this->eavAtrributeUpdateHelper = $eavAtrributeUpdateHelper;
        $this->attributeDataWithoutLoad = $attributeDataWithoutLoad;
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
            $supplierId = $postData['supplierId'] ?? "";
            $shopInputId = $postData['shopId'] ?? "";
            $leadersystemsCategoryIds = $this->attributeDataWithoutLoad->getCategoryAttributeRawValue($shopInputId, ['leadersystems_category_ids' ]);
            if ($leadersystemsCategoryIds) {
                $leadersystemsCategoryIds = explode(",", $leadersystemsCategoryIds);
            }
            $leadersystemsCategoryIds[] = $supplierId;
            $leadersystemsCategoryIds = implode(",", array_unique($leadersystemsCategoryIds));
            $this->eavAtrributeUpdateHelper->updateCategoryAttributes([$shopInputId], ['leadersystems_category_ids' => $leadersystemsCategoryIds]);
        }
        $result = $this->resultFactory->create($this->resultFactory::TYPE_RAW);
        $result->setContents($this->helper->getCategories(1));
        return $result;
    }
}
