<?php
namespace Mageseller\DickerdataImport\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Mageseller\DickerdataImport\Model\DickerdataCategoryFactory;

class Category extends Template
{
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;
    /**
     * @var DickerdataCategoryFactory
     */
    private $dickerdataCategoryFactory;

    /**
     * @param Template\Context $context
     * @param CollectionFactory $categoryCollectionFactory
     * @param DickerdataCategoryFactory $dickerdataCategoryFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CollectionFactory $categoryCollectionFactory,
        DickerdataCategoryFactory $dickerdataCategoryFactory,
        array $data = []
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->dickerdataCategoryFactory = $dickerdataCategoryFactory;
        parent::__construct($context, $data);
    }
}
