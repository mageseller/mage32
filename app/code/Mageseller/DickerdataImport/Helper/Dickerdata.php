<?php
/**
 * A Magento 2 module named Mageseller/DickerdataImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/DickerdataImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\DickerdataImport\Helper;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product as ProductEntityType;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Config\Model\ResourceModel\Config as MagentoConfig;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mageseller\DickerdataImport\Logger\DickerdataImport;
use Mageseller\DickerdataImport\Model\DickerdataCategoryFactory;
use Mageseller\Process\Model\Process;
use Mageseller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;

class Dickerdata extends AbstractHelper
{
    const FILENAME = 'vendor-file.csv';
    const TMP_FILENAME = 'vendor-file-tmp.csv';
    const DOWNLOAD_FOLDER = 'supplier/dickerdata';
    const DICKERDATA_IMPORTCONFIG_IS_ENABLE = 'dickerdata/importconfig/is_enable';
    const PRIMARY_CATEGORY = 'PrimaryCategory';
    const SECONDARY_CATEGORY = 'SecondaryCategory';
    const STOCK_CODE = 'StockCode';
    const SUPPLIER = 'dickerdata';
    const TERTIARY_CATEGORY = 'TertiaryCategory';
    const SEPERATOR = " ---|--- ";
    /**
     * /**
     *
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_mediaDirectory;

    /**
     *
     * @var unknown
     */
    protected $_dirReader;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var File
     */
    protected $fileFactory;
    /**
     * @var DateTime
     */
    protected $_dateTime;
    /**
     *
     * @var ProductCollectionFactory
     */
    protected $_productCollectionFactory;
    /**
     * @var DickerdataImport
     */
    protected $dickerdataimportLogger;
    private $apiUrl;
    /**
     * @var DickerdataCategoryFactory
     */
    private $dickerdataCategoryFactory;
    /**
     * @var CategoryFactory
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
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var ProductHelper
     */
    private $dickerdataProductHelper;
    /**
     * @var MagentoConfig
     */
    protected $configuration;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var StoreManagerInterface
     */
    private $dickerdataImageHelper;

    /**
     * @var ProcessResourceFactory
     */
    protected $processResourceFactory;
    /**
     * @var Config
     */
    private $eavConfig;
    /**
     * @var AttributeCollectionFactory
     */
    private $attributeFactory;

    /**
     * @param  Context                    $context
     * @param  Filesystem                 $filesystem
     * @param  Filesystem\DirectoryList   $dirReader
     * @param  File                       $fileFactory
     * @param  DateTime                   $dateTime
     * @param  MessageManagerInterface    $messageManager
     * @param  ResourceConnection         $resourceConnection
     * @param  MagentoConfig              $configuration
     * @param  AttributeCollectionFactory $attributeFactory
     * @param  Config                     $eavConfig
     * @param  StoreManagerInterface      $storeManager
     * @param  CategoryFactory            $categoryFactory
     * @param  ProductCollectionFactory   $productCollectionFactory
     * @param  CollectionFactory          $categoryCollectionFactory
     * @param  DickerdataImport           $dickerdataimportLogger
     * @param  DickerdataCategoryFactory  $dickerdataCategoryFactory
     * @param  ProductHelper              $dickerdataProductHelper
     * @param  ImageHelper                $dickerdataImageHelper
     * @param  ProcessResourceFactory     $processResourceFactory
     * @throws FileSystemException
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        Filesystem\DirectoryList $dirReader,
        File $fileFactory,
        DateTime $dateTime,
        MessageManagerInterface $messageManager,
        ResourceConnection $resourceConnection,
        MagentoConfig $configuration,
        AttributeCollectionFactory $attributeFactory,
        Config $eavConfig,
        StoreManagerInterface $storeManager,
        CategoryFactory $categoryFactory,
        ProductCollectionFactory $productCollectionFactory,
        CollectionFactory $categoryCollectionFactory,
        DickerdataImport $dickerdataimportLogger,
        DickerdataCategoryFactory $dickerdataCategoryFactory,
        ProductHelper $dickerdataProductHelper,
        ImageHelper $dickerdataImageHelper,
        ProcessResourceFactory $processResourceFactory
    ) {
        parent::__construct($context);
        $this->_dateTime = $dateTime;
        $this->fileFactory = $fileFactory;
        $this->scopeConfig = $context->getScopeConfig();
        $this->_dirReader = $dirReader;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->storeManager = $storeManager;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->resourceConnection = $resourceConnection;
        $this->filesystem = $filesystem;
        $this->eavConfig = $eavConfig;
        $this->attributeFactory = $attributeFactory;
        $this->processResourceFactory = $processResourceFactory;
        $this->configuration = $configuration;
        $this->dickerdataimportLogger = $dickerdataimportLogger;
        $this->dickerdataCategoryFactory = $dickerdataCategoryFactory;
        $this->dickerdataProductHelper = $dickerdataProductHelper;
        $this->dickerdataImageHelper = $dickerdataImageHelper;
    }
    /**
     * @return int
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @return int
     */
    public function getCurrentWebsiteId()
    {
        return $this->storeManager->getStore()->getWebsiteId();
    }

    /**
     * Returns a config flag
     *
     * @param  string $path
     * @param  mixed  $store
     * @return bool
     */
    public function getFlag($path, $store = null)
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Returns store locale
     *
     * @param  mixed $store
     * @return string
     */
    public function getLocale($store = null)
    {
        return $this->getValue('general/locale/code', $store);
    }
    public function getAllProductAttributes()
    {
        $collection = $this->attributeFactory->create();
        $collection
            ->addFieldToFilter('entity_type_id', $this->eavConfig->getEntityType(ProductEntityType::ENTITY)->getEntityTypeId())
            ->addFieldToFilter('frontend_input', 'select')
            ->setOrder('attribute_id', 'desc');

        $attributeCodes = [];
        foreach ($collection->getData() as $attributes) {
            $attributeCodes[] = [
                'id' => $attributes[AttributeInterface::ATTRIBUTE_ID],
                'value' => $attributes[AttributeInterface::ATTRIBUTE_CODE],
                'label' => $attributes[AttributeInterface::FRONTEND_LABEL]
            ];
        }
        return $attributeCodes;
    }
    /**
     * Get tax class id specified for shipping tax estimation
     *
     * @param  mixed $store
     * @return int
     */
    public function getShippingTaxClass($store = null)
    {
        return $this->getValue(\Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $store);
    }

    /**
     * Reads the configuration directly from the database
     *
     * @param  string $path
     * @param  string $scope
     * @param  int    $scopeId
     * @return string|false
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
     * @param  string $path
     * @param  mixed  $store
     * @return mixed
     */
    public function getValue($path, $store = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Returns store name if defined
     *
     * @param  mixed $store
     * @return string
     */
    public function getStoreName($store = null)
    {
        return $this->getValue(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_NAME, $store);
    }

    /**
     * @return bool
     */
    public function isSingleStoreMode()
    {
        return $this->storeManager->hasSingleStore();
    }
    /**
     * @param  string $entity
     * @param  mixed  $store
     * @return \DateTime|null
     */
    public function getSyncDate($entity, $store = null)
    {
        $path = "dickerdata/$entity/last_sync_$entity";

        if (null === $store) {
            $date = $this->getRawValue($path);
        } else {
            $scopeId = $this->storeManager->getStore($store)->getId();
            $date = $this->getRawValue($path, ScopeInterface::SCOPE_STORES, $scopeId);
        }

        return !empty($date) ? new \DateTime($date) : null;
    }

    /**
     * @return $this
     */
    protected function resetConfig()
    {
        $this->storeManager->getStore()->resetConfig();

        return $this;
    }

    /**
     * @param  string $entity
     * @return $this
     */
    public function resetSyncDate($entity)
    {
        $this->setValue("dickerdata/$entity/last_sync_$entity", null);

        return $this->resetConfig();
    }

    /**
     * @param  string $entity
     * @param  string $time
     * @return $this
     */
    public function setSyncDate($entity, $time = 'now')
    {
        $datetime = new \DateTime($time);
        $this->setValue("dickerdata/$entity/last_sync_$entity", $datetime->format(\DateTime::ISO8601));

        return $this->resetConfig();
    }
    /**
     * Set a config value
     *
     * @param string $path
     * @param string $value
     * @param string $scope
     * @param int    $scopeId
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
        return $this->getConfig(self::DICKERDATA_IMPORTCONFIG_IS_ENABLE);
    }

    /**
     * @param  $value
     * @param  string $scope
     * @return mixed
     */
    public function getConfig($value, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue($value, $scope);
    }
    public function importDickerdataProducts(Process $process, $since, $sendReport = true)
    {
        if (!$since && ($lastSyncDate = $this->getSyncDate('product'))) {
            $since = $lastSyncDate;
        }

        // Save last synchronization date now if file download is too long
        $this->setSyncDate('product');
        if ($since) {
            $process->output(__('Downloading products from Dickerdatafeed to Magento since %1', $since->format('Y-m-d H:i:s')), true);
            $importParams = ['updated_since' => $since->format(\DateTime::ATOM)];
        } else {
            $process->output(__('Downloading products from Dickerdata feed to Magento'), true);
        }

        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $process->output(__('Downloading file...'), true);
        $apiUrl = $this->getApiUrl();
        $filepath = $this->downloadFile($apiUrl);
        $downloadFolder = $this->_dirReader->getPath('var') . '/' . self::DOWNLOAD_FOLDER;
        $directoryRead = $this->filesystem->getDirectoryReadByPath($downloadFolder);
        $file = $directoryRead->openFile(self::FILENAME);
        $this->dickerdataProductHelper->processProducts($file, $process, $since, $sendReport);
    }
    public function importDickerdataImages(Process $process, $since, $sendReport = true)
    {
        if (!$since && ($lastSyncDate = $this->getSyncDate('images'))) {
            $since = $lastSyncDate;
        }

        // Save last synchronization date now if file download is too long
        $this->setSyncDate('images');
        if ($since) {
            $process->output(__('Downloading images from Dickerdata feed to Magento since %1', $since->format('Y-m-d H:i:s')), true);
            $importParams = ['updated_since' => $since->format(\DateTime::ATOM)];
        } else {
            $process->output(__('Downloading images from Dickerdata feed to Magento'), true);
        }

        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $process->output(__('Downloading file...'), true);
        $apiUrl = $this->getApiUrl();
        $filepath = $this->downloadFile($apiUrl);
        $xml = simplexml_load_file($filepath, null, LIBXML_NOCDATA);
        if ($xml instanceof SimpleXMLElement) {
            $items = $xml->xpath("/Catalogue/Items/Item");
            $this->dickerdataImageHelper->processProductImages($items, $process, $since, $sendReport);
        }
    }
    public function secureRip(string $str): string
    {
        return mb_convert_encoding($str, "UTF-8", "UTF-16LE");
    }
    public function importDickerdataCategory()
    {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $apiUrl = $this->getApiUrl();
        $filepath = $this->downloadFile($apiUrl);
        $categoriesWithParents = [];
        $downloadFolder = $this->_dirReader->getPath('var') . '/' . self::DOWNLOAD_FOLDER;
        $directoryRead = $this->filesystem->getDirectoryReadByPath($downloadFolder);
        $file = $directoryRead->openFile(self::FILENAME);
        $headers = array_flip($file->readCsv());

        while (false !== ($row = $file->readCsv())) {
            $categoryLevel1 = $row[$headers[self::PRIMARY_CATEGORY]] ?? "";
            $categoryLevel2 = $row[$headers[self::SECONDARY_CATEGORY]] ?? "";
            $level = implode(self::SEPERATOR, [$categoryLevel1,$categoryLevel2]);
            if ($categoryLevel1) {
                $categoriesWithParents[$categoryLevel1 . self::SEPERATOR . ''] = $level;
            }
            if ($categoryLevel2) {
                $categoriesWithParents[$categoryLevel2 . self::SEPERATOR . $categoryLevel1] = $level;
            }
        }

        /*Adding category names start*/

        $allCategories = array_map(
            function ($v) {
                $names = explode(self::SEPERATOR, $v);
                return $names[0] ? [
                'name' => $names[0],
                'parent_name' => $names[1]
                ] : [];
            }, array_keys($categoriesWithParents)
        );
        $allCategories = array_filter($allCategories);
        $collection = $this->dickerdataCategoryFactory->create()->getCollection();
        $collection->insertOnDuplicate($allCategories);
        /*Adding category names ends*/

        /*Adding parent id to child category starts*/
        $select = (clone $collection->getSelect())
            ->reset(Select::COLUMNS)
            ->columns([ 'name' => 'LOWER(CONCAT(name,"' . self::SEPERATOR . '",parent_name))','parent_name','id' => 'dickerdatacategory_id']);

        $connection = $this->resourceConnection->getConnection();
        $allCategoryIds = $connection->fetchAssoc($select);
        foreach ($categoriesWithParents as $categoryKeys => $categoryValues) {
            $names = explode(self::SEPERATOR, $categoryKeys);
            $childCategory = $names[0];
            $parentCategory = $names[1];
            $categoryValues = explode(self::SEPERATOR, $categoryValues);
            $key = array_search($parentCategory, $categoryValues);
            $parentParentCategory = "";
            if ($key > 0) {
                $parentParentCategory = $categoryValues[$key-1];
            }

            $parentId = $allCategoryIds[strtolower($parentCategory) . self::SEPERATOR . strtolower($parentParentCategory)]['id'] ?? null;

            $connection->update(
                $collection->getMainTable(),
                ['parent_id' => $parentId],
                ['name = ?' => $childCategory,'parent_name = ?' => $parentCategory]
            );
        }
        /*Adding parent id to child category ends*/
        return true;
    }

    public function getApiUrl()
    {
        if ($this->apiUrl == null) {
            $this->apiUrl = $this->getUrl();
        }
        return $this->apiUrl;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->getConfig('dickerdata/importconfig/url');
    }

    public function downloadFile($source)
    {
        //download file to var/dropship/ folder from url provided in config
        $this->dickerdataimportLogger->debug('Cron Download File : ' . $source);

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
            $this->dickerdataimportLogger->debug('Importing file Dickerdata : ' . $source);
            $ch = curl_init($source);
            $fp = fopen($filepath, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        } else {
            $this->dickerdataimportLogger->info('Import File Already Exists: ' . $filepath);
            $this->dickerdataimportLogger->debug('Importing file to tmp file: ' . $source);
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
        return isset($value) ? is_object($value) ? array_filter(
            json_decode(json_encode($value), true), function ($value) {
                return !is_array($value) && $value !== '';
            }
        ) : $value : [];
    }

    public function parseValue($value)
    {
        return isset($value) ? trim($value) : "";
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
                    if ($dickerdata_category_ids = $subcat->getData('dickerdata_category_ids')) {
                        $dickerdata_category_ids = array_filter(explode(",", $dickerdata_category_ids));
                        foreach ($dickerdata_category_ids as $dickerdata_category_id) {
                            $supplierCatName = $supplierData[$dickerdata_category_id]['name'] ?? $dickerdata_category_id;
                            $html .= "<div class='supplier-categories-name'>";
                            $html .= " =====> " . $supplierCatName . "  ";
                            $html .= ' <a href="javascript:void(0)" style="color:red;" class="remove-shop-category" data-supplier-id="' . $dickerdata_category_id . '" data-id="' . $subcat->getId() . '" >remove map</a>';
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

    public function getSupplierCatData()
    {
        if ($this->supplierCategories == null) {
            $supplierCategory = $this->getSupplierCategory();
            $select = $supplierCategory->getSelect()->reset(Select::COLUMNS)->columns(['dickerdatacategory_id', 'name']);
            $this->supplierCategories = $this->resourceConnection->getConnection()->fetchAssoc($select);
        }
        return $this->supplierCategories;
    }

    public function getSupplierCategory()
    {
        return $this->dickerdataCategoryFactory->create()->getCollection();
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
        return $html;
    }

    public function getMappedCategory()
    {
        $categoryMapArray = [];
        $categories = $this->getMagentoCategory()->getData();
        foreach ($categories as $category) {
            $dickerdataMappedCategories = explode(",", $category['dickerdata_category_ids']);
            foreach ($dickerdataMappedCategories as $dickerdataCategoryId) {
                if (isset($categoryMapArray[$dickerdataCategoryId])) {
                    $categoryMapArray[$dickerdataCategoryId] .= "<div class='shop-category-name'>  ===> " .
                        $category['name'] .
                        "</div>";
                } else {
                    $categoryMapArray[$dickerdataCategoryId] = "<div class='shop-category-name'>  ===> " .
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
            ->addAttributeToSelect('dickerdata_category_ids', 'left');
    }

    public function getSupplierTreeCategory()
    {
        $connection = $this->resourceConnection->getConnection();
        $collection = $this->dickerdataCategoryFactory->create()->getCollection();
        $select = $collection->getSelect()->reset(Select::COLUMNS)
            ->joinLeft(
                ['eav' => $this->resourceConnection->getTableName('eav_attribute')],
                'eav.attribute_id = main_table.attribute_id',
                ['attribute_name' => 'frontend_label']
            )
            ->columns(
                [
                'id' => 'dickerdatacategory_id',
                'name' => 'name',
                'parent_id' => 'parent_id',
                'attribute_id' => 'attribute_id',
                ]
            );

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

    public function getSupplierCategoryTree($supplierTreeCategories, $categoryMapArray)
    {
        $html = "<ul>";
        foreach ($supplierTreeCategories as $supplierTreeCategory) {
            $html .= "<li>";
            $supplierCategoryId = $supplierTreeCategory['id'];
            $attributeId = $supplierTreeCategory['attribute_id'];
            $attributeName = $supplierTreeCategory['attribute_name'];

            $style = isset($categoryMapArray[$supplierCategoryId]) ? "style=color:green;" : "";
            $name = isset($categoryMapArray[$supplierCategoryId]) ? $categoryMapArray[$supplierCategoryId] : "";
            $supplierCategoryName = $supplierTreeCategory['name'];
            $html .= "<a data-id='$supplierCategoryId' href='javascript:void(0)' $style>$supplierCategoryName</a>
                        $name";
            if (isset($supplierTreeCategory['childs'])) {
                $html .= $this->getSupplierCategoryTree($supplierTreeCategory['childs'], $categoryMapArray);
            }
            if ($attributeName) {
                $html .= " ==> Used as Attribute :  " . $attributeName;
            }
            $html .= "</li>";
        }
        $html .= "</ul>";
        return $html;
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
