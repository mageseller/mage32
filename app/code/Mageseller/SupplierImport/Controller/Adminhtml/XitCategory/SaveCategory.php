<?php

namespace Mageseller\SupplierImport\Controller\Adminhtml\XitCategory;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Mageseller\SupplierImport\Helper\EavAtrributeUpdateHelper;
use Mageseller\SupplierImport\Helper\EavAttributeDataWithoutLoad;
use Mageseller\SupplierImport\Helper\Xit;

class SaveCategory extends Action
{
    /** @var  Page */
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

    /**      *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Xit $helper
     * @param EavAtrributeUpdateHelper $eavAtrributeUpdateHelper
     * @param EavAttributeDataWithoutLoad $attributeDataWithoutLoad
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
            $xitCategoryIds = $this->attributeDataWithoutLoad->getCategoryAttributeRawValue($shopInputId, ['xit_category_ids' ]);
            if ($xitCategoryIds) {
                $xitCategoryIds = explode(",", $xitCategoryIds);
            }
            $xitCategoryIds[] = $supplierId;
            $xitCategoryIds = implode(",", array_unique($xitCategoryIds));
            $this->eavAtrributeUpdateHelper->updateCategoryAttributes([$shopInputId], ['xit_category_ids' => $xitCategoryIds]);
        }
        $result = $this->resultFactory->create($this->resultFactory::TYPE_RAW);
        $result->setContents($this->helper->getCategories(1));
        return $result;
    }
}
