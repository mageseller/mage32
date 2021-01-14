<?php

namespace Mageseller\IngrammicroImport\Controller\Adminhtml\IngrammicroCategory;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mageseller\IngrammicroImport\Helper\EavAtrributeUpdateHelper;
use Mageseller\IngrammicroImport\Helper\EavAttributeDataWithoutLoad;
use Mageseller\IngrammicroImport\Helper\Ingrammicro;

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
     * @param Ingrammicro $helper
     * @param EavAtrributeUpdateHelper $eavAtrributeUpdateHelper
     * @param EavAttributeDataWithoutLoad $attributeDataWithoutLoad
     * @param array $data
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
                $ingrammicroCategoryIds = $this->attributeDataWithoutLoad->getCategoryAttributeRawValue($shopId, ['ingrammicro_category_ids' ]);
                if ($ingrammicroCategoryIds) {
                    $ingrammicroCategoryIds = explode(",", $ingrammicroCategoryIds);
                    $ingrammicroCategoryIds = array_diff($ingrammicroCategoryIds, [$supplierId]);
                }
                if ($ingrammicroCategoryIds) {
                    $this->eavAtrributeUpdateHelper->updateCategoryAttributes([$shopId], ['ingrammicro_category_ids' => implode(",", $ingrammicroCategoryIds)]);
                } else {
                    $this->eavAtrributeUpdateHelper->updateCategoryAttributes([$shopId], ['ingrammicro_category_ids' => null]);
                }
            }
        }
        $result = $this->resultFactory->create($this->resultFactory::TYPE_RAW);
        $result->setContents($this->helper->getCategories(1));
        return $result;
    }
}
