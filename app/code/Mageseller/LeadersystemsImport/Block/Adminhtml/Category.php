<?php
namespace Mageseller\LeadersystemsImport\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Mageseller\LeadersystemsImport\Model\LeadersystemsCategoryFactory;

class Category extends Template
{
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;
    /**
     * @var LeadersystemsCategoryFactory
     */
    private $leadersystemsCategoryFactory;

    /**
     * @param Template\Context $context
     * @param CollectionFactory $categoryCollectionFactory
     * @param LeadersystemsCategoryFactory $leadersystemsCategoryFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CollectionFactory $categoryCollectionFactory,
        LeadersystemsCategoryFactory $leadersystemsCategoryFactory,
        array $data = []
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->leadersystemsCategoryFactory = $leadersystemsCategoryFactory;
        parent::__construct($context, $data);
    }
}
