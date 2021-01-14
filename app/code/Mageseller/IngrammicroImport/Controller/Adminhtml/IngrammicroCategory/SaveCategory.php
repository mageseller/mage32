<?php

namespace Mageseller\IngrammicroImport\Controller\Adminhtml\IngrammicroCategory;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Mageseller\IngrammicroImport\Helper\EavAtrributeUpdateHelper;
use Mageseller\IngrammicroImport\Helper\EavAttributeDataWithoutLoad;
use Mageseller\IngrammicroImport\Helper\Ingrammicro;

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
     * @param Ingrammicro $helper
     * @param EavAtrributeUpdateHelper $eavAtrributeUpdateHelper
     * @param EavAttributeDataWithoutLoad $attributeDataWithoutLoad
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Ingrammicro $helper,
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
            $ingrammicroCategoryIds = $this->attributeDataWithoutLoad->getCategoryAttributeRawValue($shopInputId, ['ingrammicro_category_ids' ]);
            if ($ingrammicroCategoryIds) {
                $ingrammicroCategoryIds = explode(",", $ingrammicroCategoryIds);
            }
            $ingrammicroCategoryIds[] = $supplierId;
            $ingrammicroCategoryIds = implode(",", array_unique($ingrammicroCategoryIds));
            $this->eavAtrributeUpdateHelper->updateCategoryAttributes([$shopInputId], ['ingrammicro_category_ids' => $ingrammicroCategoryIds]);
        }
        $result = $this->resultFactory->create($this->resultFactory::TYPE_RAW);
        $result->setContents($this->helper->getCategories(1));
        return $result;
    }
}
