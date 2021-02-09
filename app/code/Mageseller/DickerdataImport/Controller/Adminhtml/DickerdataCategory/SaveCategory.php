<?php

namespace Mageseller\DickerdataImport\Controller\Adminhtml\DickerdataCategory;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Mageseller\DickerdataImport\Helper\EavAtrributeUpdateHelper;
use Mageseller\DickerdataImport\Helper\EavAttributeDataWithoutLoad;
use Mageseller\DickerdataImport\Helper\Dickerdata;

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
     * @param Dickerdata                  $helper
     * @param EavAtrributeUpdateHelper    $eavAtrributeUpdateHelper
     * @param EavAttributeDataWithoutLoad $attributeDataWithoutLoad
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Dickerdata $helper,
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
            $dickerdataCategoryIds = $this->attributeDataWithoutLoad->getCategoryAttributeRawValue($shopInputId, ['dickerdata_category_ids' ]);
            if ($dickerdataCategoryIds) {
                $dickerdataCategoryIds = explode(",", $dickerdataCategoryIds);
            }
            $dickerdataCategoryIds[] = $supplierId;
            $dickerdataCategoryIds = implode(",", array_unique($dickerdataCategoryIds));
            $this->eavAtrributeUpdateHelper->updateCategoryAttributes([$shopInputId], ['dickerdata_category_ids' => $dickerdataCategoryIds]);
        }
        $result = $this->resultFactory->create($this->resultFactory::TYPE_RAW);
        $result->setContents($this->helper->getCategories(1));
        return $result;
    }
}
