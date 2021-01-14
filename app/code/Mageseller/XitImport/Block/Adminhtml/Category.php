<?php
namespace Mageseller\XitImport\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Mageseller\XitImport\Model\XitCategoryFactory;

class Category extends Template
{
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;
    /**
     * @var XitCategoryFactory
     */
    private $xitCategoryFactory;

    /**
     * @param Template\Context $context
     * @param CollectionFactory $categoryCollectionFactory
     * @param XitCategoryFactory $xitCategoryFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CollectionFactory $categoryCollectionFactory,
        XitCategoryFactory $xitCategoryFactory,
        array $data = []
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->xitCategoryFactory = $xitCategoryFactory;
        parent::__construct($context, $data);
    }
}
