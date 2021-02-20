<?php
/**
 * A Magento 2 module named Mageseller/LeadersystemsImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/LeadersystemsImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\LeadersystemsImport\Helper;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Config\Model\ResourceModel\Config as MagentoConfig;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mageseller\Process\Model\Process;
use Mageseller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;

class Leadersystems extends AbstractHelper
{
    const FILENAME = 'vendor-file.zip';
    const TMP_FILENAME = 'vendor-file-tmp.zip';
    const CSV_FILENAME = 'vendor-file.csv';
    const CSV_TMP_FILENAME = 'vendor-file-tmp.csv';
    const XML_FILENAME = 'vendor-file.xml';
    const XML_TMP_FILENAME = 'vendor-file-tmp.xml';
    const DOWNLOAD_FOLDER = 'supplier/leadersystems';
    const LEADERSYSTEMS_IMPORTCONFIG_IS_ENABLE = 'leadersystems/importconfig/is_enable';
    const PRIMARY_CATEGORY = 'CATEGORY NAME';
    const SECONDARY_CATEGORY = 'SUBCATEGORY NAME';
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
     * @var \Mageseller\LeadersystemsImport\Logger\LeadersystemsImport
     */
    protected $leadersystemsimportLogger;
    private $apiUrl;
    /**
     * @var \Mageseller\LeadersystemsImport\Model\LeadersystemsCategoryFactory
     */
    private $leadersystemsCategoryFactory;
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
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;
    /**
     * @var ProductHelper
     */
    private $leadersystemsProductHelper;
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
    private $leadersystemsImageHelper;

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
     * @var \Mageseller\Utility\Helper\Data
     */
    protected $utilityHelper;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\DirectoryList $dirReader
     * @param \Magento\Framework\Filesystem\Io\File $fileFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param MessageManagerInterface $messageManager
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Mageseller\LeadersystemsImport\Logger\LeadersystemsImport $leadersystemsimportLogger
     * @param \Mageseller\LeadersystemsImport\Model\LeadersystemsCategoryFactory $leadersystemsCategoryFactory
     * @param CollectionFactory $categoryCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param ProductHelper $leadersystemsProductHelper
     * @param ImageHelper $leadersystemsImageHelper
     * @param MagentoConfig $configuration
     * @param StoreManagerInterface $storeManager
     * @param ProcessResourceFactory $processResourceFactory
     * @param AttributeCollectionFactory $attributeFactory
     * @param Config $eavConfig
     * @param \Mageseller\Utility\Helper\Data $utilityHelper
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
        \Mageseller\LeadersystemsImport\Logger\LeadersystemsImport $leadersystemsimportLogger,
        \Mageseller\LeadersystemsImport\Model\LeadersystemsCategoryFactory $leadersystemsCategoryFactory,
        CollectionFactory $categoryCollectionFactory,
        ResourceConnection $resourceConnection,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Mageseller\LeadersystemsImport\Helper\ProductHelper $leadersystemsProductHelper,
        \Mageseller\LeadersystemsImport\Helper\ImageHelper $leadersystemsImageHelper,
        MagentoConfig $configuration,
        StoreManagerInterface $storeManager,
        ProcessResourceFactory $processResourceFactory,
        AttributeCollectionFactory $attributeFactory,
        Config $eavConfig,
        \Mageseller\Utility\Helper\Data $utilityHelper
    ) {
        parent::__construct($context);
        $this->_dateTime = $dateTime;
        $this->fileFactory = $fileFactory;
        $this->filesystem = $filesystem;
        $this->scopeConfig = $context->getScopeConfig();
        $this->_dirReader = $dirReader;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->storeManager = $storeManager;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->leadersystemsimportLogger = $leadersystemsimportLogger;
        $this->leadersystemsCategoryFactory = $leadersystemsCategoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->resourceConnection = $resourceConnection;
        $this->processResourceFactory = $processResourceFactory;
        $this->configuration = $configuration;
        $this->leadersystemsProductHelper = $leadersystemsProductHelper;
        $this->leadersystemsImageHelper = $leadersystemsImageHelper;
        $this->eavConfig = $eavConfig;
        $this->attributeFactory = $attributeFactory;
        $this->utilityHelper = $utilityHelper;
    }

    /**
     * @return array
     */
    public function getAllProductAttributes()
    {
        return $this->utilityHelper->getAllProductAttributes();
    }

    /**
     * @return mixed
     */
    public function getEnable()
    {
        return $this->getConfig(self::LEADERSYSTEMS_IMPORTCONFIG_IS_ENABLE);
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
    public function importLeadersystemsProducts(Process $process, $since, $sendReport = true)
    {
        if (!$since && ($lastSyncDate = $this->utilityHelper->getSyncDate("leadersystems", 'product'))) {
            $since = $lastSyncDate;
        }

        // Save last synchronization date now if file download is too long
        $this->utilityHelper->setSyncDate('leadersystems', 'product');
        if ($since) {
            $process->output(__('Downloading products from Leadersystemsfeed to Magento since %1', $since->format('Y-m-d H:i:s')), true);
            $importParams = ['updated_since' => $since->format(\DateTime::ATOM)];
        } else {
            $process->output(__('Downloading products from Leadersystems feed to Magento'), true);
        }

        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $process->output(__('Downloading file...'), true);
        $apiUrl = $this->getApiUrl();
        $filepath = $this->downloadFile($apiUrl);
        $downloadFolder = $this->_dirReader->getPath('var') . '/' . self::DOWNLOAD_FOLDER;
        $directoryRead = $this->filesystem->getDirectoryReadByPath($downloadFolder);
        $file = $directoryRead->openFile(self::CSV_FILENAME);
        $this->leadersystemsProductHelper->processProducts($file, $process, $since, $sendReport);
    }
    public function importLeadersystemsImages(Process $process, $since, $sendReport = true)
    {
        if (!$since && ($lastSyncDate = $this->utilityHelper->getSyncDate("leadersystems", 'images'))) {
            $since = $lastSyncDate;
        }

        // Save last synchronization date now if file download is too long
        $this->utilityHelper->setSyncDate("leadersystems", 'images');
        if ($since) {
            $process->output(__('Downloading images from Leadersystems feed to Magento since %1', $since->format('Y-m-d H:i:s')), true);
            $importParams = ['updated_since' => $since->format(\DateTime::ATOM)];
        } else {
            $process->output(__('Downloading images from Leadersystems feed to Magento'), true);
        }

        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $process->output(__('Downloading file...'), true);
        $apiUrl = $this->getApiUrl();
        $filepath = $this->downloadFile($apiUrl);
        $downloadFolder = $this->_dirReader->getPath('var') . '/' . self::DOWNLOAD_FOLDER;
        $directoryRead = $this->filesystem->getDirectoryReadByPath($downloadFolder);
        $file = $directoryRead->openFile(self::CSV_FILENAME);
        $this->leadersystemsImageHelper->processProductImages($file, $process, $since, $sendReport);
    }
    public function secureRip(string $str): string
    {
        return mb_convert_encoding($str, "UTF-8", "UTF-16LE");
    }
    public function importLeadersystemsCategory()
    {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $apiUrl = $this->getApiUrl();
        $filepath = $this->downloadFile($apiUrl);
        $categoriesWithParents = [];
        $downloadFolder = $this->_dirReader->getPath('var') . '/' . self::DOWNLOAD_FOLDER;
        $directoryRead = $this->filesystem->getDirectoryReadByPath($downloadFolder);
        $file = $directoryRead->openFile(self::CSV_FILENAME);
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
            },
            array_keys($categoriesWithParents)
        );
        $allCategories = array_filter($allCategories);
        $collection = $this->leadersystemsCategoryFactory->create()->getCollection();
        $collection->insertOnDuplicate($allCategories);
        /*Adding category names ends*/

        /*Adding parent id to child category starts*/
        $select = (clone $collection->getSelect())
            ->reset(Select::COLUMNS)
            ->columns(['name' => 'LOWER(CONCAT(name,"' . self::SEPERATOR . '",parent_name))','parent_name', 'id' => 'leadersystemscategory_id']);
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
        return $this->getConfig('leadersystems/importconfig/url');
    }

    public function downloadFile($source)
    {
        //download file to var/dropship/ folder from url provided in config
        $this->leadersystemsimportLogger->debug('Cron Download File : ' . $source);

        $fileName = self::FILENAME;
        $tmpFileName = self::TMP_FILENAME;
        $downloadFolder = $this->_dirReader->getPath('var') . '/' . self::DOWNLOAD_FOLDER;
        $filepath = $downloadFolder . '/' . $fileName;

        //check if directory exists
        if (!is_dir($downloadFolder)) {
            $this->fileFactory->mkdir($downloadFolder, 0775);
        }

        if (!$this->fileFactory->fileExists($filepath)) {
            $this->leadersystemsimportLogger->debug('Importing file Leadersystems : ' . $source);
            $ch = curl_init($source);
            $fp = fopen($filepath, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        } else {
            $this->leadersystemsimportLogger->info('Import File Already Exists: ' . $filepath);
            $this->leadersystemsimportLogger->debug('Importing file to tmp file: ' . $source);
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
        $zip = new \ZipArchive();

        $csvFilepath = $downloadFolder . '/' . self::CSV_FILENAME;
        $res = $zip->open($filepath);
        if ($res === true) {
            // extract it to the path we determined above
            $zip->extractTo($downloadFolder);
            $zip->close();
            $files = \glob($downloadFolder . '/*.csv', GLOB_BRACE);
            array_multisort(
                array_map('filemtime', $files),
                SORT_NUMERIC,
                SORT_DESC,
                $files
            );
            $fileName = $files[0] ?? "";
            $this->fileFactory->open(['path' => $downloadFolder]);
            $this->fileFactory->mv($fileName, self::CSV_FILENAME);
        }

        return $csvFilepath;
    }

    /**
     * @return mixed
     */
    public function getXmlUrl()
    {
        return $this->getConfig('leadersystems/importconfig/object_id');
    }

    public function parseObject($value)
    {
        return isset($value) ? is_object($value) ? array_filter(
            json_decode(json_encode($value), true),
            function ($value) {
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
                    if ($leadersystems_category_ids = $subcat->getData('leadersystems_category_ids')) {
                        $leadersystems_category_ids = array_filter(explode(",", $leadersystems_category_ids));
                        foreach ($leadersystems_category_ids as $leadersystems_category_id) {
                            $supplierCatName = $supplierData[$leadersystems_category_id]['name'] ?? $leadersystems_category_id;
                            $html .= "<div class='supplier-categories-name'>";
                            $html .= " =====> " . $supplierCatName . "  ";
                            $html .= ' <a href="javascript:void(0)" style="color:red;" class="remove-shop-category" data-supplier-id="' . $leadersystems_category_id . '" data-id="' . $subcat->getId() . '" >remove map</a>';
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
            $select = $supplierCategory->getSelect()->reset(Select::COLUMNS)->columns(['leadersystemscategory_id', 'name']);
            $this->supplierCategories = $this->resourceConnection->getConnection()->fetchAssoc($select);
        }
        return $this->supplierCategories;
    }

    public function getSupplierCategory()
    {
        return $this->leadersystemsCategoryFactory->create()->getCollection();
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

    public function getMappedCategory()
    {
        $categoryMapArray = [];
        $categories = $this->getMagentoCategory()->getData();
        foreach ($categories as $category) {
            $leadersystemsMappedCategories = explode(",", $category['leadersystems_category_ids']);
            foreach ($leadersystemsMappedCategories as $leadersystemsCategoryId) {
                if (isset($categoryMapArray[$leadersystemsCategoryId])) {
                    $categoryMapArray[$leadersystemsCategoryId] .= "<div class='shop-category-name'>  ===> " .
                        $category['name'] .
                        "</div>";
                } else {
                    $categoryMapArray[$leadersystemsCategoryId] = "<div class='shop-category-name'>  ===> " .
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
            ->addAttributeToSelect('leadersystems_category_ids', 'left');
    }

    public function getSupplierTreeCategory()
    {
        $collection = $this->leadersystemsCategoryFactory->create()->getCollection();
        $select = $collection->getSelect()->reset(Select::COLUMNS)
            ->joinLeft(
                ['eav' => $this->resourceConnection->getTableName('eav_attribute')],
                'eav.attribute_id = main_table.attribute_id',
                ['attribute_name' => 'frontend_label']
            )
            ->columns(
                [
                'id' => 'leadersystemscategory_id',
                'name' => 'name',
                'parent_id' => 'parent_id',
                'attribute_id' => 'attribute_id'
                ]
            );
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
