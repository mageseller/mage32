<?php
/**
 * A Magento 2 module named Mageseller/XitImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/XitImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\XitImport\Helper;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Store\Model\ScopeInterface;
use SimpleXMLElement;
use Magento\Config\Model\ResourceModel\Config as MagentoConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class Xit extends AbstractHelper
{
    const FILENAME = 'vendor-file.xml';
    const TMP_FILENAME = 'vendor-file-tmp.xml';
    const DOWNLOAD_FOLDER = 'supplier/xit';
    const XIT_IMPORTCONFIG_IS_ENABLE = 'xit/importconfig/is_enable';
    /**
     * /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_mediaDirectory;
    /**

    /**
     *
     * @var unknown
     */
    protected $_dirReader;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     *
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $fileFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;
    /**
     * @var \Mageseller\XitImport\Logger\XitImport
     */
    protected $xitimportLogger;
    private $apiUrl;
    /**
     * @var \Mageseller\XitImport\Model\XitCategoryFactory
     */
    private $xitCategoryFactory;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $categoryFactory;
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    private $supplierCategories;
    /**
     * @var ProductHelper
     */
    private $xitProductHelper;
    /**
     * @var MagentoConfig
     */
    protected $configuration;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Magento\Framework\Filesystem\DirectoryList $dirReader
     * @param \Magento\Framework\Filesystem\Io\File $fileFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param MessageManagerInterface $messageManager
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Mageseller\XitImport\Logger\XitImport $xitimportLogger
     * @param \Mageseller\XitImport\Model\XitCategoryFactory $xitCategoryFactory
     * @param ProductHelper $xitProductHelper
     * @param CollectionFactory $categoryCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\DirectoryList $dirReader,
        \Magento\Framework\Filesystem\Io\File $fileFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        MessageManagerInterface $messageManager,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Mageseller\XitImport\Logger\XitImport $xitimportLogger,
        \Mageseller\XitImport\Model\XitCategoryFactory $xitCategoryFactory,
        \Mageseller\XitImport\Helper\ProductHelper $xitProductHelper,
        CollectionFactory $categoryCollectionFactory,
        ResourceConnection $resourceConnection,
        MagentoConfig $configuration,
        StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory
    ) {
        parent::__construct($context);
        $this->_dateTime = $dateTime;
        $this->fileFactory = $fileFactory;
        $this->scopeConfig = $context->getScopeConfig();
        $this->_dirReader = $dirReader;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->xitimportLogger = $xitimportLogger;
        $this->xitCategoryFactory = $xitCategoryFactory;
        $this->xitProductHelper = $xitProductHelper;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->resourceConnection = $resourceConnection;
        $this->configuration = $configuration;
        $this->storeManager = $storeManager;
    }
    /**
     * @return  int
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @return  int
     */
    public function getCurrentWebsiteId()
    {
        return $this->storeManager->getStore()->getWebsiteId();
    }

    /**
     * Returns a config flag
     *
     * @param   string  $path
     * @param   mixed   $store
     * @return  bool
     */
    public function getFlag($path, $store = null)
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Returns store locale
     *
     * @param   mixed   $store
     * @return  string
     */
    public function getLocale($store = null)
    {
        return $this->getValue('general/locale/code', $store);
    }

    /**
     * Get tax class id specified for shipping tax estimation
     *
     * @param   mixed   $store
     * @return  int
     */
    public function getShippingTaxClass($store = null)
    {
        return $this->getValue(\Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $store);
    }

    /**
     * Reads the configuration directly from the database
     *
     * @param   string  $path
     * @param   string  $scope
     * @param   int     $scopeId
     * @return  string|false
     */
    public function getRawValue($path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        $connection = $this->configuration->getConnection();

        $select = $connection->select()
            ->from($this->configuration->getMainTable(), 'value')
            ->where('path = ?', $path)
            ->where('scope = ?', $scope)
            ->where('scope_id = ?', $scopeId);

        return $connection->fetchOne($select);
    }

    /**
     * Returns a config value
     *
     * @param   string  $path
     * @param   mixed   $store
     * @return  mixed
     */
    public function getValue($path, $store = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Returns store name if defined
     *
     * @param   mixed   $store
     * @return  string
     */
    public function getStoreName($store = null)
    {
        return $this->getValue(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_NAME, $store);
    }

    /**
     * @return  bool
     */
    public function isSingleStoreMode()
    {
        return $this->storeManager->hasSingleStore();
    }
    /**
     * @param   string  $entity
     * @param   mixed   $store
     * @return  \DateTime|null
     */
    public function getSyncDate($entity, $store = null)
    {
        $path = "xit/$entity/last_sync_$entity";

        if (null === $store) {
            $date = $this->getRawValue($path);
        } else {
            $scopeId = $this->storeManager->getStore($store)->getId();
            $date = $this->getRawValue($path, ScopeInterface::SCOPE_STORES, $scopeId);
        }

        return !empty($date) ? new \DateTime($date) : null;
    }

    /**
     * @return  $this
     */
    protected function resetConfig()
    {
        $this->storeManager->getStore()->resetConfig();

        return $this;
    }

    /**
     * @param   string  $entity
     * @return  $this
     */
    public function resetSyncDate($entity)
    {
        $this->setValue("xit/$entity/last_sync_$entity", null);

        return $this->resetConfig();
    }

    /**
     * @param   string  $entity
     * @param   string  $time
     * @return  $this
     */
    public function setSyncDate($entity, $time = 'now')
    {
        $datetime = new \DateTime($time);
        $this->setValue("xit/$entity/last_sync_$entity", $datetime->format(\DateTime::ISO8601));

        return $this->resetConfig();
    }
    /**
     * Set a config value
     *
     * @param   string  $path
     * @param   string  $value
     * @param   string  $scope
     * @param   int     $scopeId
     */
    public function setValue($path, $value, $scope = 'default', $scopeId = 0)
    {
        $this->configuration->saveConfig($path, $value, $scope, $scopeId);
    }


    /**
     * @return mixed
     */
    public function getEnable()
    {
        return $this->getConfig(self::XIT_IMPORTCONFIG_IS_ENABLE);
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
    public function importXitProducts()
    {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $apiUrl = $this->getApiUrl();
        $filepath = $this->downloadFile($apiUrl);
        $xml = simplexml_load_file($filepath);
        if ($xml instanceof SimpleXMLElement) {
            $items = $xml->xpath("/Catalogue/Items/Item");
            $this->xitProductHelper->processProducts($items);
        }
    }
    public function importXitCategory()
    {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $apiUrl = $this->getApiUrl();
        $filepath = $this->downloadFile($apiUrl);
        $allCategories = [];
        $categoriesWithParents = [];
        $xml = simplexml_load_file($filepath);
        if ($xml instanceof SimpleXMLElement) {
            $items = $xml->xpath("/Catalogue/Items/Item");

            foreach ($items as $item) {
                $categories = $this->parseObject($item->ItemDetail->Classifications->Classification);
                unset($categories['@attributes']);
                $allCategories = array_unique(array_merge($allCategories, $categories));
                $lastCat = "";
                foreach ($categories as $category) {
                    $categoriesWithParents[$category] = $lastCat;
                    $lastCat = $category;
                }
            }
        }
        /*Adding category names start*/
        $collection = $this->xitCategoryFactory->create()->getCollection();
        $allCategories = array_map(function ($v) {
            return ['name' => $v];
        }, $allCategories);
        $collection->insertOnDuplicate($allCategories);
        /*Adding category names ends*/

        /*Adding parent id to child category starts*/
        $select = (clone $collection->getSelect())
                    ->reset(Select::COLUMNS)
                    ->columns(['name' => 'name','id' => 'xitcategory_id']);
        $connection = $this->resourceConnection->getConnection();
        $allCategoryIds = $connection->fetchAssoc($select);
        foreach ($categoriesWithParents as $childCategory => $parentCategory) {
            $parentId = $allCategoryIds[$parentCategory]['id'] ?? 0;
            $connection->update(
                $collection->getMainTable(),
                ['parent_id' => $parentId],
                ['name = ?' => $childCategory]
            );
        }
        /*Adding parent id to child category ends*/
        return true;
    }

    public function getApiUrl()
    {
        if ($this->apiUrl == null) {
            $this->apiUrl = $this->getUrl() . "?ObjectID=" . $this->getObjectId() . "&Token=" . $this->getToken();
        }
        return $this->apiUrl;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->getConfig('xit/importconfig/url');
    }

    /**
     * @return mixed
     */
    public function getObjectId()
    {
        return $this->getConfig('xit/importconfig/object_id');
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->getConfig('xit/importconfig/token');
    }

    public function downloadFile($source)
    {
        //download file to var/dropship/ folder from url provided in config
        $this->xitimportLogger->debug('Cron Download File : ' . $source);

        $fileName = self::FILENAME;
        $tmpFileName = self::TMP_FILENAME;
        $downloadFolder = $this->_dirReader->getPath('var') . '/' . self::DOWNLOAD_FOLDER;
        $filepath = $downloadFolder . '/' . $fileName;
        return $filepath;
        //check if directory exists
        if (!is_dir($downloadFolder)) {
            $this->fileFactory->mkdir($downloadFolder, 0775);
        }

        if (!$this->fileFactory->fileExists($filepath)) {
            $this->xitimportLogger->debug('Importing file Xit : ' . $source);
            $ch = curl_init($source);
            $fp = fopen($filepath, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        } else {
            $this->xitimportLogger->info('Import File Already Exists: ' . $filepath);
            $this->xitimportLogger->debug('Importing file to tmp file: ' . $source);
            $tmpFilePath = $downloadFolder . '/' . $tmpFileName;
            $ch = curl_init($source);
            $fp = fopen($tmpFilePath, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
            $this->fileFactory->open(['path' => $downloadFolder]);
            $this->fileFactory->mv($tmpFileName, $fileName);
        }
        return $filepath;
    }

    public function parseObject($value)
    {
        return isset($value) ? is_object($value) ? array_filter(json_decode(json_encode($value), true), function ($value) {
            return !is_array($value) && $value !== '';
        }) : $value : [];
    }

    public function parseValue($value)
    {
        return isset($value) ? trim($value) : "";
    }
    public function getSupplierCatData()
    {
        if ($this->supplierCategories == null) {
            $supplierCategory = $this->getSupplierCategory();
            $select = $supplierCategory->getSelect()->reset(Select::COLUMNS)->columns(['xitcategory_id','name']);
            $this->supplierCategories = $this->resourceConnection->getConnection()->fetchAssoc($select);
        }
        return $this->supplierCategories;
    }
    public function getCategories($catId = 1)
    {
        $supplierData = $this->getSupplierCatData();
        $subcategory = $this->categoryFactory->create()->load($catId);
        $subcats = $subcategory->getChildrenCategories();
        $html = null;
        $s = "";
        if ($catId == 1) {
            $s = 'id="expList"';
        }
        $html .= "<ul  ";
        $html .= $s;
        $html .= ">";
        if ($subcats) {
            foreach ($subcats as $subcat) {
                $subcat->load($subcat->getId());
                if ($subcat->getIsActive()) {
                    $subcat_url = $subcat->getUrl();
                    $html .= '<li><a href="javascript:void(0)" class="shop-category-li" data-id="' . $subcat->getId() . '" >' . $subcat->getName() . "</a> ";
                    if ($xit_category_ids = $subcat->getData('xit_category_ids')) {
                        $xit_category_ids = array_filter(explode(",", $xit_category_ids));
                        foreach ($xit_category_ids as $xit_category_id) {
                            $supplierCatName = $supplierData[$xit_category_id]['name'] ?? $xit_category_id;
                            $html .= "<div class='supplier-categories-name'>";
                            $html .= " =====> " . $supplierCatName . "  ";
                            $html .= ' <a href="javascript:void(0)" style="color:red;" class="remove-shop-category" data-supplier-id="' . $xit_category_id . '" data-id="' . $subcat->getId() . '" >remove map</a>';
                            $html .= "</div>";
                        }
                    }
                    $html .= '</li>';
                    $html .= $this->getCategories($subcat->getId());
                }
            }
        }
        $html .= "</ul>";

        return $html;
    }

    public function getCategoryById($categoryId)
    {
        $category = $this->categoryFactory->create();
        $category->load($categoryId);
        return $category;
    }

    public function getSupplierCategoryData()
    {
        $categoryMapArray = $this->getMappedCategory();
        $supplierTreeCategories = $this->getSupplierTreeCategory();
        $html = $this->getSupplierCategoryTree($supplierTreeCategories, $categoryMapArray);
        /*$html = "<ul>";
        foreach ($supplierCategories as $supplierCategory) {
            $supplierCategoryId = $supplierCategory->getId();
            $style = isset($categoryMapArray[$supplierCategoryId]) ? "style=color:green;" : "";
            $name = isset($categoryMapArray[$supplierCategoryId]) ? $categoryMapArray[$supplierCategoryId] : "";
            $supplierCategoryName = $supplierCategory->getName();
            $html .= "<li>
                        <a data-id='$supplierCategoryId' href='javascript:void(0)' $style>$supplierCategoryName</a>
                        $name
                    </li>";
        }
        $html .= "</ul>";*/
        return $html;
    }
    public function getSupplierCategoryTree($supplierTreeCategories, $categoryMapArray)
    {
        $html = "<ul>";
        foreach ($supplierTreeCategories as $supplierTreeCategory) {
            $html .= "<li>";
            $supplierCategoryId = $supplierTreeCategory['id'];
            $style = isset($categoryMapArray[$supplierCategoryId]) ? "style=color:green;" : "";
            $name = isset($categoryMapArray[$supplierCategoryId]) ? $categoryMapArray[$supplierCategoryId] : "";
            $supplierCategoryName = $supplierTreeCategory['name'];
            $html .= "<a data-id='$supplierCategoryId' href='javascript:void(0)' $style>$supplierCategoryName</a>
                        $name";
            if (isset($supplierTreeCategory['childs'])) {
                $html .= $this->getSupplierCategoryTree($supplierTreeCategory['childs'], $categoryMapArray);
            }
            $html .= "</li>";
        }
        $html .= "</ul>";
        return $html;
    }

    public function getMappedCategory()
    {
        $categoryMapArray = [];
        $categories = $this->getMagentoCategory()->getData();
        foreach ($categories as $category) {
            $xitMappedCategories = explode(",", $category['xit_category_ids']);
            foreach ($xitMappedCategories as $xitCategoryId) {
                if (isset($categoryMapArray[$xitCategoryId])) {
                    $categoryMapArray[$xitCategoryId] .=  "<div class='shop-category-name'>  ===> " .
                                                                $category['name'] .
                                                            "</div>";
                } else {
                    $categoryMapArray[$xitCategoryId] =  "<div class='shop-category-name'>  ===> " .
                                                            $category['name'] .
                                                        "</div>";
                }
            }
        }
        return $categoryMapArray;
    }

    public function getMagentoCategory()
    {
        return $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('name', 'left')
            ->addAttributeToSelect('xit_category_ids', 'left');
    }

    public function getSupplierCategory()
    {
        return $this->xitCategoryFactory->create()->getCollection();
    }
    public function getSupplierTreeCategory()
    {
        $collection = $this->xitCategoryFactory->create()->getCollection();
        $select = $collection->getSelect()->reset(Select::COLUMNS)
                    ->columns([
                        'id' => 'xitcategory_id',
                        'name' => 'name',
                        'parent_id' => 'parent_id'
                    ]);
        $connection = $this->resourceConnection->getConnection();
        $categoryWithParents = $connection->fetchAll($select);
        $tree = $this->buildTreeFromArray($categoryWithParents);
        return $tree;
    }
    public function buildTreeFromArray($items)
    {
        $childs = [];

        foreach ($items as &$item) {
            $childs[$item['parent_id'] ?? 0][] = &$item;
        }

        unset($item);

        foreach ($items as &$item) {
            if (isset($childs[$item['id']])) {
                $item['childs'] = $childs[$item['id']];
            }
        }

        return $childs[0] ?? [];
    }

    public function buildTreeFromObjects($items)
    {
        $childs = [];

        foreach ($items as $item) {
            $childs[$item->parent_id ?? 0][] = $item;
        }

        foreach ($items as $item) {
            if (isset($childs[$item->id])) {
                $item->childs = $childs[$item->id];
            }
        }

        return $childs[0] ?? [];
    }


}
