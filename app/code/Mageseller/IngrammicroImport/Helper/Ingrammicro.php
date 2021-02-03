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

use Magento\Catalog\Model\Product as ProductEntityType;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Filesystem\File\ReadInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Ingrammicro extends AbstractHelper
{
    const FILENAME = 'vendor-file.csv';
    const TMP_FILENAME = 'vendor-file-tmp.csv';
    const DOWNLOAD_FOLDER = 'supplier/ingrammicro';
    const INGRAMMICRO_IMPORTCONFIG_IS_ENABLE = 'ingrammicro/importconfig/is_enable';
    const CATEGORY_ID = 'Category ID';
    const CATEGORY = 'Category';
    const SUB_CATEGORY = 'Sub-Category';
    const PRODUCT_GROUP_CODE = 'ProductGroupCode';
    const DESCRIPTION = 'Description';
    const SEPERATOR = " ---|--- ";
    /**
     * /**
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
    private $conn;
    private $dir;
    private $sftp;
    /**
     * @var Config
     */
    private $eavConfig;
    /**
     * @var AttributeCollectionFactory
     */
    private $attributeFactory;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\DirectoryList $dirReader
     * @param \Magento\Framework\Filesystem\Io\File $fileFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param MessageManagerInterface $messageManager
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Mageseller\IngrammicroImport\Logger\IngrammicroImport $ingrammicroimportLogger
     * @param \Mageseller\IngrammicroImport\Model\IngrammicroCategoryFactory $ingrammicroCategoryFactory
     * @param CollectionFactory $categoryCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param AttributeCollectionFactory $attributeFactory
     * @param Config $eavConfig
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
        \Mageseller\IngrammicroImport\Logger\IngrammicroImport $ingrammicroimportLogger,
        \Mageseller\IngrammicroImport\Model\IngrammicroCategoryFactory $ingrammicroCategoryFactory,
        CollectionFactory $categoryCollectionFactory,
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        AttributeCollectionFactory $attributeFactory,
        Config $eavConfig
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
        $this->ingrammicroimportLogger = $ingrammicroimportLogger;
        $this->ingrammicroCategoryFactory = $ingrammicroCategoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->resourceConnection = $resourceConnection;
        $this->eavConfig = $eavConfig;
        $this->attributeFactory = $attributeFactory;
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
     * @return mixed
     */
    public function getEnable()
    {
        return $this->getConfig(self::INGRAMMICRO_IMPORTCONFIG_IS_ENABLE);
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
        if (empty($_FILES['groups']['tmp_name']['importconfig']['fields']['import_category_file']['value'])) {
            return $this;
        }

        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $filePath = $_FILES['groups']['tmp_name']['importconfig']['fields']['import_category_file']['value'];
        $this->downloadFile();
        /**
         * @var ReadInterface $object
         */
        $file = $this->getCsvFile($filePath);
        $allCategories = [];
        $fileName = self::FILENAME;
        $tmpFileName = self::TMP_FILENAME;
        $downloadFolder = $this->_dirReader->getPath('var') . '/' . self::DOWNLOAD_FOLDER;
        $filepath = $downloadFolder . '/' . $fileName;
        $directoryRead = $this->filesystem->getDirectoryReadByPath($downloadFolder);

        $file2 =  $directoryRead->openFile($fileName);
        try {
            /*Adding category names start*/
            $allCsvCategories = [];
            $headers = array_flip($file->readCsv(0, "\t"));
            while (false !== ($row = $file->readCsv(0, "\t"))) {
                $catId = (int) $row[$headers[self::PRODUCT_GROUP_CODE]] ?? '';
                $catName =  $row[$headers[self::DESCRIPTION]]  ?? '';
                $allCsvCategories[$catId] = $catName;
            }
            /*Adding category names ends*/
            /*Creating category tree start*/
            $headers = array_flip($file2->readCsv(0, ","));
            while (false !== ($row = $file2->readCsv(0, ","))) {
                $categoryLevel1 =  $row[$headers[self::CATEGORY]] ?? '';
                $categoryLevel2 =  $row[$headers[self::SUB_CATEGORY]]  ?? 0;
                $categoryLevel3 =  $row[$headers[self::CATEGORY_ID]]  ?? 0;
                $parentId1 = ltrim($categoryLevel1, '0');
                if ($categoryLevel1 == '000') {
                    $categoryLevel1 = 0;
                    $parentId1 = '';
                } else {
                    $categoryLevel1 = $parentId1;
                }
                $categoriesWithParents['1_' . intval($categoryLevel1) . self::SEPERATOR . ''] = $categoryLevel1;
                if ($categoryLevel2) {
                    $categoriesWithParents['2_' . intval($categoryLevel2) . self::SEPERATOR . '1_' . intval($categoryLevel1)] = $parentId1 . $categoryLevel2;
                    if ($categoryLevel3) {
                        $categoriesWithParents['3_' . intval($categoryLevel3) . self::SEPERATOR . '2_' . intval($categoryLevel2)] = $parentId1 . $categoryLevel2 . $categoryLevel3;
                    }
                }
            }
            $allCategories = [];
            foreach ($categoriesWithParents as $k => $v) {
                $names = explode(self::SEPERATOR, $k);
                if (isset($allCategories[$names[0]])) {
                    continue;
                }
                $supplierCategoryIds = explode('_', $names[0]);
                $ingrammicrocategoryId = str_replace('_', '', $names[0]);
                $parentId = str_replace('_', '', $names[1]);
                $allCategories[intval($ingrammicrocategoryId)] = [
                    'ingrammicrocategory_id' => intval($ingrammicrocategoryId),
                    'name' => $allCsvCategories[intval($v)] ?? $ingrammicrocategoryId,
                    'supplier_cat_id' => $supplierCategoryIds[1] ?? "",
                    'parent_id' => intval($parentId),
                ];
            }
            /*echo "<pre>";
            print_r($allCsvCategories);
            print_r($categoriesWithParents);
            print_r($allCategories);
            die;*/
            $collection = $this->ingrammicroCategoryFactory->create()->getCollection();

            /*Adding parent id to child category starts*/
            $select = (clone $collection->getSelect())
                ->reset(Select::COLUMNS)
                ->columns([ 'ingrammicrocategory_id' ]);
            $connection = $this->resourceConnection->getConnection();
            $allCategoryIds = $connection->fetchAssoc($select);

            $isCategories = [];
            foreach ($allCategories as $category) {
                $categoryId = ltrim($category['ingrammicrocategory_id'], "0");
                $name = $category['name'];
                $parentId = ltrim($category['parent_id'], "0");
                $supplier_cat_id = $category['supplier_cat_id'];
                if (isset($isCategories[$categoryId])) {
                    continue;
                }
                if (isset($allCategoryIds[$categoryId])) {
                    $connection->update(
                        $collection->getMainTable(),
                        [ 'parent_id' => trim($parentId),'name' => $name],
                        ['ingrammicrocategory_id = ?' => trim($categoryId)]
                    );
                } else {
                    $connection->insert(
                        $collection->getMainTable(),
                        [
                            'ingrammicrocategory_id' => $categoryId,
                            'supplier_cat_id' => $supplier_cat_id,
                            'parent_id' => $parentId,
                            'name' => $name
                        ]
                    );
                }
                $isCategories[$categoryId] = true;
            }
            /*Creating category tree ends*/
        } catch (\Exception $e) {
            echo $e->getMessage();
            die;
            $this->ingrammicroimportLogger->critical($e);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while importing .')
            );
        } finally {
            $file->close();
            $file2->close();
        }
        return;
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

    public function downloadFile()
    {
        $fileName = self::FILENAME;
        $tmpFileName = self::TMP_FILENAME;
        $downloadFolder = $this->_dirReader->getPath('var') . '/' . self::DOWNLOAD_FOLDER;
        $filepath = $downloadFolder . '/' . $fileName;

        //check if directory exists
        if (!is_dir($downloadFolder)) {
            $this->fileFactory->mkdir($downloadFolder, 0775);
        }
        $ftpRemoteDir = $this->getConfig('ingrammicro/importconfig/path');
        $ftpHost = $this->getConfig('ingrammicro/importconfig/host');
        $ftpUser = $this->getConfig('ingrammicro/importconfig/username');
        $ftpPass = $this->getConfig('ingrammicro/importconfig/password');
        $tmpLocation = $downloadFolder;
        $prodFileName = "STDPRICE_FULL.TXT.zip";
        /*$this->setRemoteDirectory($ftpRemoteDir);
        $this->ftpConnect($ftpHost, $ftpUser, $ftpPass);
        $this->downloadFileFromFtp($tmpLocation . "/" . $prodFileName, $prodFileName, $ftpRemoteDir);*/
        // Checking the download file extension
        $fileObj = new \SplFileInfo($tmpLocation . "/" . $prodFileName);

        if (strtolower($fileObj->getExtension()) === strtolower('ZIP')) {

            // init the zip archive object and open so i can unzip the file
            $zip = new \ZipArchive();
            $result = $zip->open($tmpLocation . "/" . $prodFileName);

            // checking the result of the open zip
            if ($result === true) {
                $zip->extractTo($tmpLocation);
                $zip->close();
            } else {
                throw new \Exception("Zip: Failed, code: " . $result, 1);
            }

            // prepareing the name of the file because inside of the archive is a TXT file
            $filename = str_replace("." . $fileObj->getExtension(), '', $prodFileName);
            $this->fileFactory->open(['path' => $downloadFolder]);
            $this->fileFactory->mv($filename, $fileName);
        } else {
            // if the file is not archived the file name is ok
            $filename = $prodFileName;
        }
        return $filepath;
    }
    /**
     * Set Remote Directory
     * @param string $dir
     */
    public function setRemoteDirectory($dir)
    {
        $this->dir = $dir;
    }

    /**
     * Connect to FTP
     * @param $host
     * @param $user
     * @param $pass
     * @param int $port
     * @throws \Exception
     */
    public function ftpConnect($host, $user, $pass, $port=22)
    {
        $this->conn = \ssh2_connect($host, $port);
        if ($this->conn === false) {
            throw new Exception("Could not connect to $host on port $port.");
        }
        if (!\ssh2_auth_password($this->conn, $user, $pass)) {
            throw new Exception("Could not authenticate with username $user " .
                "and password *******.");
        }
        $this->sftp = \ssh2_sftp($this->conn);
        if (!$this->sftp) {
            throw new Exception("Could not initialize SFTP subsystem.");
        }
        return;// $this->conn;
    }

    /**
     * Download a file from the server
     *
     * @param string $localFile
     * @param string $remoteFile
     * @param string $type
     * @return bool
     */
    public function downloadFileFromFtp($localFile, $remoteFile, $ftpRemoteDir, $type=FTP_BINARY)
    {
        return $this->receiveFile($ftpRemoteDir, $remoteFile, $localFile);
    }
    public function receiveFile($ftpRemoteDir, $remote_file, $local_file)
    {
        $sftp = $this->sftp;
        $stream = @fopen("ssh2.sftp://{$sftp}{$ftpRemoteDir}/{$remote_file}", 'r');
        if (! $stream) {
            throw new \Exception("Could not open file: $remote_file");
        }

        $contents = stream_get_contents($stream);
        file_put_contents($local_file, $contents);
        @fclose($stream);
    }

    /**
     * Close the ftp connection
     *
     * @param none
     * @return bool
     */
    public function ftpClose()
    {
        return "";
        return \ftp_close($this->conn);
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
                    ->joinLeft(
                        ['eav' => $this->resourceConnection->getTableName('eav_attribute')],
                        'eav.attribute_id = main_table.attribute_id',
                        ['attribute_name' => 'frontend_label']
                    )
                    ->columns([
                        'id' => 'ingrammicrocategory_id',
                        'name' => 'name',
                        'parent_id' => 'parent_id',
                        'attribute_id' => 'attribute_id'
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
