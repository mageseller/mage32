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

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Store\Model\ScopeInterface;

class Dickerdata extends AbstractHelper
{
    const FILENAME = 'vendor-file.json';
    const TMP_FILENAME = 'vendor-file-tmp.json';
    const DOWNLOAD_FOLDER = 'supplier/dickerdata';
    const DICKERDATA_IMPORTCONFIG_IS_ENABLE = 'dickerdata/importconfig/is_enable';
    const PRIMARY_CATEGORY = 'PrimaryCategory';
    const SECONDARY_CATEGORY = 'SecondaryCategory';
    const TERTIARY_CATEGORY = 'TertiaryCategory';
    const SEPERATOR = " ---|--- ";
    /**
     * /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_mediaDirectory;
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $_storeManager;
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
     * @var \Mageseller\DickerdataImport\Logger\DickerdataImport
     */
    protected $dickerdataimportLogger;
    private $apiUrl;
    /**
     * @var \Mageseller\DickerdataImport\Model\DickerdataCategoryFactory
     */
    private $dickerdataCategoryFactory;
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
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Magento\Framework\Filesystem\DirectoryList $dirReader
     * @param \Magento\Framework\Filesystem\Io\File $fileFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param MessageManagerInterface $messageManager
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Mageseller\DickerdataImport\Logger\DickerdataImport $dickerdataimportLogger
     * @param \Mageseller\DickerdataImport\Model\DickerdataCategoryFactory $dickerdataCategoryFactory
     * @param CollectionFactory $categoryCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\Filesystem\DirectoryList $dirReader,
        \Magento\Framework\Filesystem\Io\File $fileFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        MessageManagerInterface $messageManager,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Mageseller\DickerdataImport\Logger\DickerdataImport $dickerdataimportLogger,
        \Mageseller\DickerdataImport\Model\DickerdataCategoryFactory $dickerdataCategoryFactory,
        CollectionFactory $categoryCollectionFactory,
        ResourceConnection $resourceConnection,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory
    ) {
        parent::__construct($context);
        $this->_dateTime = $dateTime;
        $this->fileFactory = $fileFactory;
        $this->scopeConfig = $context->getScopeConfig();
        $this->_dirReader = $dirReader;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->_storeManager = $storeManager;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->dickerdataimportLogger = $dickerdataimportLogger;
        $this->dickerdataCategoryFactory = $dickerdataCategoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @return mixed
     */
    public function getEnable()
    {
        return $this->getConfig(self::DICKERDATA_IMPORTCONFIG_IS_ENABLE);
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

    public function importDickerdataCategory()
    {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $apiUrl = $this->getApiUrl();
        $filepath = $this->downloadFile($apiUrl);
        $stream = file_get_contents($filepath);
        $items = json_decode($stream, true);
        $categoriesWithParents = [];
        foreach ($items as $jsonItem) {
            $primaryCategory = $jsonItem[self::PRIMARY_CATEGORY] ?? "";
            $secondaryCategory = $jsonItem[self::SECONDARY_CATEGORY] ?? "";
            $tertiaryCategory = $jsonItem[self::TERTIARY_CATEGORY] ?? "";

            $level = implode(self::SEPERATOR, [$primaryCategory,$secondaryCategory,$tertiaryCategory]);
            if ($primaryCategory) {
                $categoriesWithParents[$primaryCategory . self::SEPERATOR . ''] = $level;
                if ($secondaryCategory) {
                    $categoriesWithParents[$secondaryCategory . self::SEPERATOR . $primaryCategory] = $level;
                    if ($tertiaryCategory) {
                        $categoriesWithParents[$tertiaryCategory . self::SEPERATOR . $secondaryCategory] = $level;
                    }
                }
            }
        }
        /*Adding category names start*/

        $allCategories = array_map(function ($v) {
            $names = explode(self::SEPERATOR, $v);
            return $names[0] ? [
                'name' => $names[0],
                'parent_name' => $names[1]
            ] : [];
        }, array_keys($categoriesWithParents));
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
        return isset($value) ? is_object($value) ? array_filter(json_decode(json_encode($value), true), function ($value) {
            return !is_array($value) && $value !== '';
        }) : $value : [];
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
        $collection = $this->dickerdataCategoryFactory->create()->getCollection();
        $select = $collection->getSelect()->reset(Select::COLUMNS)
            ->columns([
                'id' => 'dickerdatacategory_id',
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
