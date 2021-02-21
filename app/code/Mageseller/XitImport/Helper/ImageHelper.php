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

use Magento\Framework\App\Helper\Context;
use Mageseller\Process\Model\Process;
use Mageseller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Mageseller\ProductImport\Api\Data\ProductStoreView;
use Mageseller\ProductImport\Api\Data\SimpleProduct;
use Mageseller\ProductImport\Api\ImportConfig;
use Mageseller\ProductImport\Api\ImporterFactory;
use Mageseller\XitImport\Logger\XitImport;

class ImageHelper extends ProductHelper
{
    const FILENAME = 'vendor-file.xml';
    const TMP_FILENAME = 'vendor-file-tmp.xml';
    const DOWNLOAD_FOLDER = 'supplier/xit';
    const INGRAMMICRO_IMPORTCONFIG_IS_ENABLE = 'xit/importconfig/is_enable';
    const ATTRIBUTE_PRODUCT_SKU = 'sku';
    const PDF_FOLDER = 'devicesPdf';

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
     * ImageHelper constructor.
     * @param Context $context
     * @param XitImport $xitimportLogger
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
        XitImport $xitimportLogger,
        ProcessResourceFactory $processResourceFactory,
        ImporterFactory $importerFactory,
        \Mageseller\Utility\Helper\Data $utilityHelper,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\Filesystem\DirectoryList $dirReader,
        \Magento\Framework\Filesystem\Io\File $fileFactory
    ) {
        parent::__construct($context, $xitimportLogger, $processResourceFactory, $importerFactory, $utilityHelper);
        $this->_storeManager = $storeManager;
        $this->mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $this->_dirReader = $dirReader;
        $this->fileFactory = $fileFactory;
    }

    public function processProductImages($items, Process $process, $since, $sendReport = true)
    {
        $isFlush = false;
        try {
            $oldUpdateAt = $since ? date_parse($since->format('Y-m-d H:i:s')) : 0;

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
            foreach ($items as $item) {
                try {
                    ++$i;
                    $this->start = microtime(true);

                    //$this->importImages($item, $j, $importer);
                    $sku =  (string) $item->ItemDetail->ManufacturerPartID;
                    $flag = false;
                    $product = new SimpleProduct($sku);
                    if (isset($item->Images->Image->URL)) {
                        foreach ($item->Images as $node) {
                            $updatedAt = (string)$node->Image->UpdatedAt;
                            $currentUpdateAT = date_parse($updatedAt);
                            //if ($oldUpdateAt <= $currentUpdateAT) {
                            $imageUrl = (string) $node->Image->URL;
                            if ($imageUrl) {
                                $image = $product->addImage($imageUrl);
                                $product->global()->setImageRole($image, ProductStoreView::BASE_IMAGE);
                                $product->global()->setImageRole($image, ProductStoreView::THUMBNAIL_IMAGE);
                                $product->global()->setImageRole($image, ProductStoreView::SMALL_IMAGE);
                                $flag = true;
                            }
                            //}
                        }
                    }
                    if (isset($item->Brochures->Brochure->URL)) {
                        $brochureSourceUrl = (string)$item->Brochures->Brochure->URL;

                        $path_parts = pathinfo($brochureSourceUrl);
                        if ($brochureSourceUrl) {
                            $brochureUrl = $this->mediaUrl . self::PDF_FOLDER . '/' . $path_parts['basename'];
                            $isDownloaded = $this->downloadPdfFile($brochureSourceUrl, $path_parts['basename']);
                            if (is_array($isDownloaded) && isset($isDownloaded['error'])) {
                                $message = sprintf("%s: failed! sku = %s error = %s \n", ($j + 1), $product->getSku(), $isDownloaded['error']);
                                if ($message) {
                                    $process->output($message);
                                }
                            } elseif ($isDownloaded) {
                                $message = sprintf("%s: Downloading Brochure sku = %s for url = %s \n", ($j + 1), $product->getSku(), $brochureSourceUrl);
                                $process->output($message);
                                $product->global()->setCustomAttribute('brochure_url', $brochureUrl);
                                $flag = true;
                            }
                        }
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
    public function downloadPdfFile($source, $fileName)
    {
        try {
            $downloadFolderPdf = $this->_dirReader->getPath('pub') . '/media/' . self::PDF_FOLDER;
            //check if directory exists
            if (!is_dir($downloadFolderPdf)) {
                $this->fileFactory->mkdir($downloadFolderPdf, 0775);
            }
            $filepath = $downloadFolderPdf . '/' . $fileName;
            //@todo check if file is changed or not
            if (!$this->fileFactory->fileExists($filepath)) {
                $ch = curl_init($source);
                $fp = fopen($filepath, 'wb');
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_exec($ch);
                curl_close($ch);
                fclose($fp);
                $this->utilityHelper->uploadToS3(self::PDF_FOLDER . '/' . $fileName);
                return true;
            }
            return true;
        } catch (\Exception $e) {
            return ['error' => "Error {$e->getMessage()} for url $source"];
        }
    }
}
