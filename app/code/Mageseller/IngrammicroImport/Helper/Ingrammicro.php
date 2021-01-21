<?php
/**
 * A Magento 2 module named Mageseller/IngrammicroImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/IngrammicroImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\IngrammicroImport\Helper;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Filesystem\File\ReadInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Store\Model\ScopeInterface;

class Ingrammicro extends AbstractHelper
{
    const FILENAME = 'vendor-file.csv';
    const TMP_FILENAME = 'vendor-file-tmp.csv';
    const DOWNLOAD_FOLDER = 'supplier/ingrammicro';
    const XIT_IMPORTCONFIG_IS_ENABLE = 'ingrammicro/importconfig/is_enable';
    const CATEGORY_ID = 'Category ID';
    const SUB_CATEGORY = 'Sub-Category';
    const PRODUCT_GROUP_CODE = 'ProductGroupCode';
    const DESCRIPTION = 'Description';
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
     * @var \Mageseller\IngrammicroImport\Logger\IngrammicroImport
     */
    protected $ingrammicroimportLogger;
    private $apiUrl;
    /**
     * @var \Mageseller\IngrammicroImport\Model\IngrammicroCategoryFactory
     */
    private $ingrammicroCategoryFactory;
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
     * Filesystem instance
     *
     * @var \Magento\Framework\Filesystem
     * @since 100.1.0
     */
    protected $filesystem;
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Magento\Framework\Filesystem\DirectoryList $dirReader
     * @param \Magento\Framework\Filesystem\Io\File $fileFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param MessageManagerInterface $messageManager
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Mageseller\IngrammicroImport\Logger\IngrammicroImport $ingrammicroimportLogger
     * @param \Mageseller\IngrammicroImport\Model\IngrammicroCategoryFactory $ingrammicroCategoryFactory
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
        \Mageseller\IngrammicroImport\Logger\IngrammicroImport $ingrammicroimportLogger,
        \Mageseller\IngrammicroImport\Model\IngrammicroCategoryFactory $ingrammicroCategoryFactory,
        CollectionFactory $categoryCollectionFactory,
        ResourceConnection $resourceConnection,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory
    ) {
        parent::__construct($context);
        $this->_dateTime = $dateTime;
        $this->fileFactory = $fileFactory;
        $this->filesystem = $filesystem;
        $this->scopeConfig = $context->getScopeConfig();
        $this->_dirReader = $dirReader;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->_storeManager = $storeManager;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->ingrammicroimportLogger = $ingrammicroimportLogger;
        $this->ingrammicroCategoryFactory = $ingrammicroCategoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->resourceConnection = $resourceConnection;
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
    /**
     * @param string $filePath
     * @return \Magento\Framework\Filesystem\File\ReadInterface
     */
    private function getCsvFile($filePath)
    {
        $pathInfo = pathinfo($filePath);
        $dirName = isset($pathInfo['dirname']) ? $pathInfo['dirname'] : '';
        $fileName = isset($pathInfo['basename']) ? $pathInfo['basename'] : '';

        $directoryRead = $this->filesystem->getDirectoryReadByPath($dirName);

        return $directoryRead->openFile($fileName);
    }
    public function importIngrammicroCategory()
    {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        if (empty($_FILES['groups']['tmp_name']['importconfig']['fields']['import_category_file']['value'])) {
            return $this;
        }
        $filePath = $_FILES['groups']['tmp_name']['importconfig']['fields']['import_category_file']['value'];

        /**
         * @var ReadInterface $object
         */
        $file = $this->getCsvFile($filePath);
        $allCategories = [];
        $fileName = self::FILENAME;
        $tmpFileName = self::TMP_FILENAME;
        $downloadFolder = $this->_dirReader->getPath('var') . '/' . self::DOWNLOAD_FOLDER;
        //$filepath = $downloadFolder . '/' . $fileName;
        //$directoryRead = $this->filesystem->getDirectoryReadByPath($downloadFolder);

        //$file2 =  $directoryRead->openFile($fileName);
        try {
            /*Adding category names start*/
            $headers = array_flip($file->readCsv(0, "\t"));
            while (false !== ($row = $file->readCsv(0, "\t"))) {
                $catId = (int) $row[$headers[self::PRODUCT_GROUP_CODE]] ?? '';
                $catName =  $row[$headers[self::DESCRIPTION]]  ?? '';

                $allCategories[] = [
                        'ingrammicrocategory_id' => ltrim($catId, "0"),
                        'name' => $catName,
                    ];
            }
            $collection = $this->ingrammicroCategoryFactory->create()->getCollection();
            $collection->insertOnDuplicate($allCategories);

            //$collection = $this->ingrammicroCategoryFactory->create()->getCollection();
            /*$select = (clone $collection->getSelect())
                ->reset(Select::COLUMNS)
                ->columns(['supplier_cat_id' => 'supplier_cat_id','id' => 'ingrammicrocategory_id']);
            $connection = $this->resourceConnection->getConnection();
            $allCategoryIds = $connection->fetchAssoc($select);*/

            /*$categoriesWithParents = [];
            $headers = array_flip($file2->readCsv(0, ","));
            $i = 0;
            $connection = $this->resourceConnection->getConnection();
            while (false !== ($row = $file2->readCsv(0, ","))) {
                $categoryLevel1 =  $row[$headers[self::CATEGORY_ID]] ?? 0;
                $categoryLevel2 =  $row[$headers[self::SUB_CATEGORY]]  ?? 0;
                $categoriesWithParents[ltrim($categoryLevel1, "0")] = null;
                if ($categoryLevel2) {
                    $categoriesWithParents[ltrim($categoryLevel2, "0")] = ltrim($categoryLevel1, "0");
                    $connection->update(
                        $collection->getMainTable(),
                        [ 'parent_id' => ltrim($categoryLevel1)],
                        ['ingrammicrocategory_id = ?' => ltrim($categoryLevel2)]
                    );
                }
                $i++;
            }*/
             //$collection->insertOnDuplicate($parentMap);
            /*Adding category names ends*/
        } catch (\Exception $e) {
            echo $e->getMessage();
            die;
            $this->ingrammicroimportLogger->critical($e);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while importing .')
            );
        } finally {
            $file->close();
            //$file2->close();
        }
        return;
//        return $this;
//        $apiUrl = $this->getApiUrl();
 //       $filepath = $this->downloadFile($apiUrl);
//        $allCategories = [];
//        $categoriesWithParents = [];
//        /*Adding parent id to child category starts*/
//        $select = (clone $collection->getSelect())
//                    ->reset(Select::COLUMNS)
//                    ->columns(['name' => 'name','id' => 'ingrammicrocategory_id']);
//        $connection = $this->resourceConnection->getConnection();
//        $allCategoryIds = $connection->fetchAssoc($select);
//        $parentMap = [];
//        foreach ($categoriesWithParents as $childCategory => $parentCategory) {
//            $parentMap[] = [
//                'name' => $childCategory,
//                'parent_id' => $allCategoryIds[$parentCategory]['id'] ?? 0
//            ];
//        }
//        $collection->insertOnDuplicate($parentMap);
//        /*Adding parent id to child category ends*/
//        return true;
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
        return $this->getConfig('ingrammicro/importconfig/url');
    }

    public function downloadFile($source)
    {
        //download file to var/dropship/ folder from url provided in config
        $this->ingrammicroimportLogger->debug('Cron Download File : ' . $source);

        $fileName = self::FILENAME;
        $tmpFileName = self::TMP_FILENAME;
        $downloadFolder = $this->_dirReader->getPath('var') . '/' . self::DOWNLOAD_FOLDER;
        $filepath = $downloadFolder . '/' . $fileName;

        //check if directory exists
        if (!is_dir($downloadFolder)) {
            $this->fileFactory->mkdir($downloadFolder, 0775);
        }

        if (!$this->fileFactory->fileExists($filepath)) {
            $this->ingrammicroimportLogger->debug('Importing file Ingrammicro : ' . $source);
            $ch = curl_init($source);
            $fp = fopen($filepath, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        } else {
            $this->ingrammicroimportLogger->info('Import File Already Exists: ' . $filepath);
            $this->ingrammicroimportLogger->debug('Importing file to tmp file: ' . $source);
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
            $select = $supplierCategory->getSelect()->reset(Select::COLUMNS)->columns(['ingrammicrocategory_id','name']);
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
                    if ($ingrammicro_category_ids = $subcat->getData('ingrammicro_category_ids')) {
                        $ingrammicro_category_ids = array_filter(explode(",", $ingrammicro_category_ids));
                        foreach ($ingrammicro_category_ids as $ingrammicro_category_id) {
                            $supplierCatName = $supplierData[$ingrammicro_category_id]['name'] ?? $ingrammicro_category_id;
                            $html .= "<div class='supplier-categories-name'>";
                            $html .= " =====> " . $supplierCatName . "  ";
                            $html .= ' <a href="javascript:void(0)" style="color:red;" class="remove-shop-category" data-supplier-id="' . $ingrammicro_category_id . '" data-id="' . $subcat->getId() . '" >remove map</a>';
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
            $ingrammicroMappedCategories = explode(",", $category['ingrammicro_category_ids']);
            foreach ($ingrammicroMappedCategories as $ingrammicroCategoryId) {
                if (isset($categoryMapArray[$ingrammicroCategoryId])) {
                    $categoryMapArray[$ingrammicroCategoryId] .=  "<div class='shop-category-name'>  ===> " .
                                                                $category['name'] .
                                                            "</div>";
                } else {
                    $categoryMapArray[$ingrammicroCategoryId] =  "<div class='shop-category-name'>  ===> " .
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
            ->addAttributeToSelect('ingrammicro_category_ids', 'left');
    }

    public function getSupplierCategory()
    {
        return $this->ingrammicroCategoryFactory->create()->getCollection();
    }
    public function getSupplierTreeCategory()
    {
        $collection = $this->ingrammicroCategoryFactory->create()->getCollection();
        $select = $collection->getSelect()->reset(Select::COLUMNS)
                    ->columns([
                        'id' => 'ingrammicrocategory_id',
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
