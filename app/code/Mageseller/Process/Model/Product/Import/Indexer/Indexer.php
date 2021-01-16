<?php
namespace Mageseller\Process\Model\Product\Import\Indexer;


use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Indexer\Product\Category as CategoryIndexer;
use Magento\Catalog\Model\Indexer\Product\Eav\Processor as EavIndexer;
use Magento\Catalog\Model\Indexer\Product\Flat\Processor as ProductFlatIndexer;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexer;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor as StockIndexer;
use Magento\CatalogRule\Model\Indexer\Product\ProductRuleProcessor as ProductRuleIndexer;
use Magento\CatalogSearch\Model\Indexer\Fulltext\Processor as FulltextIndexer;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Registry;
use Magento\Indexer\Model\Indexer\CollectionFactory;
use Magento\Catalog\Helper\Product as MagentoProductHelper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Indexer extends AbstractHelper
{
    const XML_PATH_XIT_ENABLE_INDEXING_IMPORT         = 'xit/product_import_indexing/enable_indexing_import';
    const XML_PATH_XIT_ENABLE_INDEXING_IMPORT_AFTER   = 'xit/product_import_indexing/enable_indexing_import_after';


    /**
     * @var MagentoProductHelper
     */
    protected $_catalogProduct;

    /**
     * @var IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CollectionFactory
     */
    protected $indexerCollectionFactory;

    /**
     * @var EventManagerInterface
     */
    protected $_eventManager;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     *
     */
    protected $scopeConfig;

    /**
     * @var array
     */
    protected $managedIndexers = [
        PriceIndexer::INDEXER_ID,
        CategoryIndexer::INDEXER_ID,
        EavIndexer::INDEXER_ID,
        ProductFlatIndexer::INDEXER_ID,
        StockIndexer::INDEXER_ID,
        ProductRuleIndexer::INDEXER_ID,
        FulltextIndexer::INDEXER_ID
    ];
    /**
     * @var McmConfigHelper
     */
    protected $mcmConfigHelper;

    /**
     * @var array
     */
    public $productIdsForIndexers = [];

    /**
     * @param XitHelper $xitHelper
     * @param MagentoProductHelper $catalogProduct
     * @param IndexerRegistry $indexerRegistry
     * @param Registry $registry
     * @param CollectionFactory $indexerCollectionFactory
     * @param EventManagerInterface $eventManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        MagentoProductHelper $catalogProduct,
        IndexerRegistry $indexerRegistry,
        Registry $registry,
        CollectionFactory $indexerCollectionFactory,
        EventManagerInterface $eventManager
    ) {
        parent::__construct($context);
        $this->scopeConfig = $context->getScopeConfig();
        $this->_catalogProduct          = $catalogProduct;
        $this->indexerRegistry          = $indexerRegistry;
        $this->registry                 = $registry;
        $this->indexerCollectionFactory = $indexerCollectionFactory;
        $this->_eventManager            = $eventManager;
    }
    /**
     * @param $value
     * @param string $scope
     * @return mixed
     */
    public function getConfig($value, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue($value, $scope);
    }
    /**
     * @return mixed
     */
    public function isEnabledIndexingImport()
    {
        return $this->getConfig(self::XML_PATH_XIT_ENABLE_INDEXING_IMPORT);
    }

    /**
     * @return mixed
     */
    public function isEnabledIndexingImportAfter()
    {
        return $this->getConfig(self::XML_PATH_XIT_ENABLE_INDEXING_IMPORT_AFTER);
    }
    /**
     * Init available indexers
     */
    public function initIndexers()
    {
        if ($this->shouldIndex()) {
            return;
        }

        foreach ($this->managedIndexers as $indexerId) {
            $this->addIndexer($indexerId);
        }

        // Allow to add other indexers
        $this->_eventManager->dispatch('mageseller_product_import_indexer_init', [
            'indexer' => $this
        ]);

        $this->registry->register('mageseller_import_no_indexer', true, true);
    }

    /**
     * @param   string  $indexerId
     */
    public function addIndexer($indexerId)
    {
        if ($this->isEnabled($indexerId)) {
            return;
        }

        try {
            // Test if indexer exists
            $indexer = $this->indexerRegistry->get($indexerId);
            if ($indexer && !$indexer->isScheduled()) {
                $this->productIdsForIndexers[$indexerId] = [];
                if (!$this->shouldReindex()) {
                    $indexer->invalidate();
                }
            }
        } catch (\Exception $e) {
            // We must continue
        }
    }

    /**
     * @param   string  $indexerId
     * @return  bool
     */
    public function isEnabled($indexerId)
    {
        return isset($this->productIdsForIndexers[$indexerId]);
    }

    /**
     * @param   Product $product
     */
    public function setIdsToIndex(Product $product)
    {
        if (!$this->shouldReindex()) {
            return;
        }

        if ($this->isEnabled(PriceIndexer::INDEXER_ID)
            && ($product->isObjectNew() || $this->_catalogProduct->isDataForPriceIndexerWasChanged($product))
        ) {
            $this->productIdsForIndexers[PriceIndexer::INDEXER_ID][] = $product->getId();
        }

        if ($this->isEnabled(EavIndexer::INDEXER_ID)
            && ($product->isObjectNew() || $product->isDataChanged())
        ) {
            $this->productIdsForIndexers[EavIndexer::INDEXER_ID][] = $product->getId();
        }

        if ($this->isEnabled(CategoryIndexer::INDEXER_ID)
            && ($this->_catalogProduct->isDataForProductCategoryIndexerWasChanged($product) || $product->isDeleted())
        ) {
            $this->productIdsForIndexers[CategoryIndexer::INDEXER_ID][] = $product->getId();
        }

        if ($this->isEnabled(ProductFlatIndexer::INDEXER_ID)) {
            $this->productIdsForIndexers[ProductFlatIndexer::INDEXER_ID][] = $product->getId();
        }

        //if ($this->isEnabled(StockIndexer::INDEXER_ID)) {
        // Marketplace stock do not change when importing product
        //}

        if ($this->isEnabled(ProductRuleIndexer::INDEXER_ID)) {
            $this->productIdsForIndexers[ProductRuleIndexer::INDEXER_ID][] = $product->getId();
        }

        if ($this->isEnabled(FulltextIndexer::INDEXER_ID)) {
            $this->productIdsForIndexers[FulltextIndexer::INDEXER_ID][] = $product->getId();
        }

        // Allow to add other indexers
        $this->_eventManager->dispatch('mageseller_product_import_indexer_add_id', [
            'indexer' => $this,
            'product' => $product,
        ]);
    }

    /**
     * @return  array
     */
    public function getIdsToIndex()
    {
        return $this->productIdsForIndexers;
    }

    /**
     * @return  bool
     */
    public function shouldIndex()
    {
        if ($this->isEnabledIndexingImport()) {
            return true;
        }

        return false;
    }

    /**
     * @return  bool
     */
    public function shouldReindex()
    {
        return $this->isEnabledIndexingImportAfter()
            && !$this->isEnabledIndexingImport();
    }

    /**
     * @throws  \Exception
     */
    public function reindex()
    {
        if (!$this->shouldReindex()) {
            return;
        }

        $this->registry->unregister('mageseller_import_no_indexer');

        foreach ($this->productIdsForIndexers as $indexerId => $productIds) {
            try {
                $idx = $this->indexerRegistry->get($indexerId);

                // We retest to be sure
                if (!$idx->isWorking() && !empty($productIds)) {
                    $idx->reindexList($productIds);
                }
            } catch (\Exception $e) {
                // We must continue
            }
        }
    }
}
