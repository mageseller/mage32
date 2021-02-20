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

use Magento\Framework\App\Helper\Context;
use Mageseller\IngrammicroImport\Logger\IngrammicroImport;
use Mageseller\Process\Model\Process;
use Mageseller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Mageseller\ProductImport\Api\Data\ProductStoreView;
use Mageseller\ProductImport\Api\Data\SimpleProduct;
use Mageseller\ProductImport\Api\ImportConfig;
use Mageseller\ProductImport\Api\ImporterFactory;

class ImageHelper extends ProductHelper
{
    const BASE_SOURCE_URL = "https://www.imvendorportal.com/prodpictures/";
    /**
     * @var
     */
    protected $mediaUrl;
    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $_dirReader;
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $fileFactory;
    /**
     * @var array
     */
    protected $supplierPartId;

    /**
     * ImageHelper constructor.
     * @param Context $context
     * @param IngrammicroImport $ingrammicroimportLogger
     * @param ProcessResourceFactory $processResourceFactory
     * @param ImporterFactory $importerFactory
     * @param \Mageseller\Utility\Helper\Data $utilityHelper
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Magento\Framework\Filesystem\DirectoryList $dirReader
     * @param \Magento\Framework\Filesystem\Io\File $fileFactory
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        Context $context,
        IngrammicroImport $ingrammicroimportLogger,
        ProcessResourceFactory $processResourceFactory,
        ImporterFactory $importerFactory,
        \Mageseller\Utility\Helper\Data $utilityHelper,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\Filesystem\DirectoryList $dirReader,
        \Magento\Framework\Filesystem\Io\File $fileFactory
    ) {
        parent::__construct($context, $ingrammicroimportLogger, $processResourceFactory, $importerFactory, $utilityHelper);
        $this->_storeManager = $storeManager;
        $this->mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $this->_dirReader = $dirReader;
        $this->fileFactory = $fileFactory;
    }

    public function processProductImages($file, Process $process, $since, $sendReport = true)
    {
        $this->supplierPartId = $this->utilityHelper->getAllSkusWithSupplierPartId(self::SUPPLIER);
        $isFlush = false;
        try {
            /*
            Array
            (
                [product_id] => 0
                [sequence_id] => 1
                [image_path] => 2
            )
            */
            $oldUpdateAt = $since ? date_parse($since->format('Y-m-d H:i:s')) : 0;
            $allIngrammicroSkus = $this->utilityHelper->getAllSkus(self::SUPPLIER);
            $config = new ImportConfig();
            $config->duplicateUrlKeyStrategy = ImportConfig::DUPLICATE_KEY_STRATEGY_ADD_SERIAL;
            $config->existingImageStrategy = ImportConfig::EXISTING_IMAGE_STRATEGY_CHECK_IMPORT_DIR;
            $config->existingSslVersion = ImportConfig::SSL_VERSION_1;
            //$config->existingImageStrategy = ImportConfig::EXISTING_IMAGE_STRATEGY_HTTP_CACHING;
            $productIdsToReindex = [];
            // a callback function to postprocess imported products
            $config->resultCallback = function (\Mageseller\ProductImport\Api\Data\Product $product) use (&$process,&$time,&$productIdsToReindex,&$importer) {
                $time = round(microtime(true) - $this->start, 2);
                if ($product->isOk()) {
                    $productIdsToReindex[] = $product->id;
                    $message = sprintf("%s: success! sku = %s, id = %s  ( $time s)\n", $product->lineNumber, $product->getSku(), $product->id);
                } else {
                    $message = sprintf("%s: failed! sku = %s error = %s ( $time s)\n", $product->lineNumber, $product->getSku(), implode('; ', $product->getErrors()));
                }
                if (isset($message)) {
                    $process->output($message);
                }
                $this->start = microtime(true);
            };
            $importer = $this->importerFactory->createImporter($config);

            $processResource = $this->processResourceFactory->create();
            $processResource->save($process);
            $i = 0; // Line number
            $j = 0; // Line number
            $this->headers = array_flip($file->readCsv(0, "\t"));
            while (false !== ($row = $file->readCsv(0, "\t"))) {
                try {
                    ++$i;
                    $this->start = microtime(true);
                    $sku = $this->supplierPartId[$row[$this->headers['product_id']]] ?? "";
                    if (!in_array($sku, $allIngrammicroSkus)) {
                        continue;
                    }
                    $flag = false;
                    $product = new SimpleProduct($sku);
                    $imageUrl = self::BASE_SOURCE_URL . $row[$this->headers['image_path']];
                    if ($imageUrl) {
                        $image = $product->addImage($imageUrl);
                        $product->global()->setImageRole($image, ProductStoreView::BASE_IMAGE);
                        $product->global()->setImageRole($image, ProductStoreView::THUMBNAIL_IMAGE);
                        $product->global()->setImageRole($image, ProductStoreView::SMALL_IMAGE);
                        $flag = true;
                    }
                    if ($flag) {
                        $product->lineNumber = $j + 1;
                        $j++;
                        $importer->importSimpleProduct($product);
                    }
                    $time = round(microtime(true) - $this->start, 2);
                    $isFlush = false;
                    if ($i % 5 === 0) {
                        $processResource->save($process);
                        if (!$isFlush) {
                            $this->start = microtime(true);
                            $importer->flush();
                            $importer = $this->importerFactory->createImporter($config);
                            $isFlush = true;
                        }
                    }
                } catch (WarningException $e) {
                    $message = __("Warning on sku %1: {$e->getMessage()}", $sku);
                    $process->output($message);
                } catch (\Exception $e) {
                    $error = __("Error for sku %1: {$e->getMessage()}", $sku);

                    $process->output($error);
                    echo $e->getTraceAsString();
                    die;
                }
            }
        } catch (\Exception $e) {
            $process->fail($e->getMessage());
            throw $e;
        } finally {
            if (!$isFlush) {
                $this->start = microtime(true);
                $importer->flush();
            }
            // Reindex
            $process->output(__('Reindexing...'), true);
            $this->utilityHelper->reindexProducts($productIdsToReindex);
        }
        $process->output(__('Done!'));
    }
}
