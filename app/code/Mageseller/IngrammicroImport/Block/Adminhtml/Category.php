<?php
namespace Mageseller\IngrammicroImport\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Mageseller\IngrammicroImport\Model\IngrammicroCategoryFactory;

class Category extends Template
{
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;
    /**
     * @var IngrammicroCategoryFactory
     */
    private $ingrammicroCategoryFactory;

    /**
     * @param Template\Context           $context
     * @param CollectionFactory          $categoryCollectionFactory
     * @param IngrammicroCategoryFactory $ingrammicroCategoryFactory
     * @param array                      $data
     */
    public function __construct(
        Template\Context $context,
        CollectionFactory $categoryCollectionFactory,
        IngrammicroCategoryFactory $ingrammicroCategoryFactory,
        array $data = []
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->ingrammicroCategoryFactory = $ingrammicroCategoryFactory;
        parent::__construct($context, $data);
    }
}
