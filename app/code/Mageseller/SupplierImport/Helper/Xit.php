<?php
/**
 * A Magento 2 module named Mageseller/SupplierImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/SupplierImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\SupplierImport\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Store\Model\ScopeInterface;

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
     * @var \Magento\Store\Model\StoreManager
     */
    protected $_storeManager;
    protected $_formulaFactory;
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
     * @var \Mageseller\SupplierImport\Logger\XitImport
     */
    protected $xitimportLogger;
    private $apiUrl;
    /**
     * @var \Mageseller\SupplierImport\Model\XitCategoryFactory
     */
    private $xitCategoryFactory;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $categoryFactory;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Magento\Framework\Filesystem\DirectoryList $dirReader
     * @param \Magento\Framework\Filesystem\Io\File $fileFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param MessageManagerInterface $messageManager
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Mageseller\SupplierImport\Logger\XitImport $xitimportLogger
     * @param \Mageseller\SupplierImport\Model\XitCategoryFactory $xitCategoryFactory
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
        \Mageseller\SupplierImport\Logger\XitImport $xitimportLogger,
        \Mageseller\SupplierImport\Model\XitCategoryFactory $xitCategoryFactory,
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
        $this->xitimportLogger = $xitimportLogger;
        $this->xitCategoryFactory = $xitCategoryFactory;
        $this->categoryFactory = $categoryFactory;
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

    public function importXitCategory()
    {
        $apiUrl = $this->getApiUrl();
        $filepath = $this->downloadFile($apiUrl);
        $categories = [];
        $xml = simplexml_load_file($filepath);
        if ($xml instanceof \SimpleXMLElement) {
            $items = $xml->xpath("/Catalogue/Items/Item");
            foreach ($items as $item) {
                $category = $this->parseObject($item->ItemDetail->Classifications->Classification);
                unset($category['@attributes']);
                $categories = array_unique(array_merge($categories, $category));
            }
        }
        $collection = $this->xitCategoryFactory->create()->getCollection();
        $categories = array_map(function ($v) {
            return ['name' => $v];
        }, $categories);
        $collection->insertOnDuplicate($categories);
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
            return $filepath;
            $this->xitimportLogger->info('Import File Already Exists: ' . $filepath);
            $this->xitimportLogger->debug('Importing file to tmp file: ' . $source);
            $this->fileFactory->open(['path' => $downloadFolder]);
            $this->fileFactory->write($tmpFileName, @file_get_contents($source), 0755);
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

    public function stripInvalidXml($value)
    {
        $ret = "";
        $current;
        if (empty($value)) {
            return $ret;
        }

        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $current = ord($value{$i});
            if (($current == 0x9) ||
                ($current == 0xA) ||
                ($current == 0xD) ||
                (($current >= 0x20) && ($current <= 0xD7FF)) ||
                (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                (($current >= 0x10000) && ($current <= 0x10FFFF))) {
                $ret .= chr($current);
            } else {
                $ret .= " ";
            }
        }
        return $ret;
    }

    public function parseValue($value)
    {
        return isset($value) ? trim($value) : "";
    }
    public function getCategories($catId=1)
    {
        $subcategory = $this->categoryFactory->create()->load($catId);
        $subcats = $subcategory->getChildrenCategories();
        $html = null;
        $s="";
        if ($catId ==1) {
            $s='id="expList"';
        }
        $html .= "<ul  ";
        $html .= $s;
        $html .= ">";
        if ($subcats) {
            foreach ($subcats as $subcat) {
                $subcat->load($subcat->getId());
                if ($subcat->getIsActive()) {
                    $subcat_url = $subcat->getUrl();
                    $html .= '<li><a href="javascript:void(0)" class="shop-category-li" data="' . $subcat->getId() . '" >' . $subcat->getName() . "</a> ";
                    if ($subcat->getMapCategory()) {
                        $html .= " =====> " . $subcat->getData('xit_category_ids') . "  ";
                        $html .= ' <a href="javascript:void(0)" style="color:red;" class="remove-shop-category" data="' . $subcat->getId() . '" >remove map</a>';
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
        $supplierCategories = $this->xitCategoryFactory->create()->getCollection();
        $categoryArray = $supplierCategories->getColumnValues('name');

        $categories = $this->categoryFactory->create()->getCollection()->addAttributeToSelect('*');
        $categoryNameArray = [];
        foreach ($categories as $category) {
            $mappedCategories = explode(",", $category->getData('xit_category_ids'));
            foreach ($mappedCategories as $mapCategory) {
                if ($mapCategory) {
                    $categoryNameArray[] = $mapCategory;
                    $categoryMapArray[$mapCategory] = $category->getName();
                }
            }
        }

        $html = "<ul>";
        foreach ($categoryArray as $category) {
            $html.='<li><a href="javascript:void(0)"';
            if (in_array($category, $categoryNameArray)) {
                $html.="style=color:green";
            }
            $html.= ">";
            $html.= $category . "</a>";
            if (in_array($category, $categoryNameArray)) {
                $html.=  " ===> " . $categoryMapArray[$category] . "</li>";
            }
        }
        $html.= "</ul>";

        return $html;
    }
}
