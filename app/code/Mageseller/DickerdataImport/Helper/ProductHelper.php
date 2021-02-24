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

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mageseller\DickerdataImport\Logger\DickerdataImport;
use Mageseller\Process\Model\Process;
use Mageseller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Mageseller\ProductImport\Api\Data\ProductStockItem;
use Mageseller\ProductImport\Api\Data\ProductStoreView;
use Mageseller\ProductImport\Api\Data\SimpleProduct;
use Mageseller\ProductImport\Api\ImportConfig;
use Mageseller\ProductImport\Api\ImporterFactory;

class ProductHelper extends AbstractHelper
{
    const SUPPLIER = 'dickerdata';
    const SEPERATOR = " ---|--- ";
    const STOCK_CODE = 'StockCode';
    const STOCK_DESCRIPTION = 'StockDescription';

    /**
     * @var DickerdataImport
     */
    protected $dickerdataimportLogger;
    /**
     * @var ProcessResourceFactory
     */
    protected $processResourceFactory;
    /**
     * @var ImporterFactory
     */
    private $importerFactory;
    /**
     * @var float|string
     */
    protected $start;
    /**
     * @var array
     */
    protected $productIdsToReindex;
    /**
     * @var array
     */
    protected $existingDickerdataCategoryAttributeIds;

    /**
     * @var array|mixed|string|null
     */
    protected $optionReplaceMents;
    /**
     * @var \Mageseller\Utility\Helper\Data
     */
    protected $utilityHelper;
    /**
     * @var array|string
     */
    protected $backOrderValues;
    /**
     * @var array
     */
    protected $existingSkusWithSupplier;
    /**
     * @var array
     */
    protected $existingDickerdataCategoryIds;
    /**
     * @var array
     */
    protected $existingSkus;
    /**
     * @var array
     */
    protected $headers;
    /**
     * @var array
     */
    protected $supplierOptionId;
    /**
     * @param Context $context
     * @param DickerdataImport $dickerdataimportLogger
     * @param ProcessResourceFactory $processResourceFactory
     * @param ImporterFactory $importerFactory
     * @param \Mageseller\Utility\Helper\Data $utilityHelper
     */
    public function __construct(
        Context $context,
        DickerdataImport $dickerdataimportLogger,
        ProcessResourceFactory $processResourceFactory,
        ImporterFactory $importerFactory,
        \Mageseller\Utility\Helper\Data $utilityHelper
    ) {
        parent::__construct($context);
        $this->dickerdataimportLogger = $dickerdataimportLogger;
        $this->processResourceFactory = $processResourceFactory;
        $this->importerFactory = $importerFactory;
        $this->utilityHelper = $utilityHelper;
    }

    public function processProducts($file, Process $process, $since, $sendReport = true)
    {
        try {
            /*
             * Array
            (
            [StockCode] => 0
            [Vendor] => 1
            [VendorStockCode] => 2
            [StockDescription] => 3
            [PrimaryCategory] => 4
            [SecondaryCategory] => 5
            [TertiaryCategory] => 6
            [RRPEx] => 7
            [DealerEx] => 8
            [StockAvailable] => 9
            [ETA] => 10
            [Status] => 11
            [Type] => 12
            [BundledItem1] => 13
            [BundledItem2] => 14
            [BundledItem3] => 15
            [BundledItem4] => 16
            [BundledItem5] => 17
            )*/
            $allDickerdataSkus = $this->utilityHelper->getAllSkus(self::SUPPLIER);
            $allSkus = [];

            $this->headers = array_flip($file->readCsv());

            $items = [];
            while (false !== ($row = $file->readCsv())) {
                $items[] = $row;
                $allSkus[] = trim($row[$this->headers[self::STOCK_CODE]]);
            }
            $option = $this->utilityHelper->loadOptionValues('supplier');
            $this->supplierOptionId = $option[self::SUPPLIER] ?? "";
            $this->existingSkusWithSupplier = $this->utilityHelper->getExistingSkusWithSupplier($allSkus);
            //$this->existingSkus = $this->utilityHelper->getExistingSkus($allSkus);
            $this->existingDickerdataCategoryIds = $this->utilityHelper->getExistingCategoryIds('dickerdata');
            $this->existingDickerdataCategoryAttributeIds = $this->utilityHelper->getExistingCategoryAttributeIds('dickerdata');
            $this->optionReplaceMents =  $this->utilityHelper->getOptionReplaceMents();
            $this->backOrderValues = $this->utilityHelper->getBackOrderValues('dickerdata');

            $attributes = array_values($this->existingDickerdataCategoryAttributeIds);
            $attributes[] = "supplier";
            $attributes[] = "brand";

            $config = new ImportConfig();
            $config->autoCreateOptionAttributes = array_unique($attributes);
            $config->duplicateUrlKeyStrategy = ImportConfig::DUPLICATE_KEY_STRATEGY_ADD_SERIAL;
            // a callback function to postprocess imported products
            $config->resultCallback = function (\Mageseller\ProductImport\Api\Data\Product $product) use (&$process, &$importer) {
                $time = round(microtime(true) - $this->start, 2);
                if ($product->isOk()) {
                    $this->productIdsToReindex[] = $product->id;
                    if ($product->getIsUpdate()) {
                        $price = $product->global()->getCustomAttribute(ProductStoreView::ATTR_PRICE);
                        $message = sprintf("%s: successfully updated with  price = %s, sku = %s, id = %s  ( $time s)\n", $product->lineNumber, $price, $product->getSku(), $product->id);
                    } elseif ($product->getIsDisabled()) {
                        $message = sprintf("%s: successfully disabled sku = %s, id = %s  ( $time s)\n", $product->lineNumber, $product->getSku(), $product->id);
                    } else {
                        $message = sprintf("%s: successfully imported sku = %s, id = %s  ( $time s)\n", $product->lineNumber, $product->getSku(), $product->id);
                    }
                } else {
                    $message = sprintf("%s: failed! sku = %s error = %s ( $time s)\n", $product->lineNumber, $product->getSku(), implode('; ', $product->getErrors()));
                }
                if (isset($message)) {
                    $process->output($message);
                }
                $this->start = microtime(true);
            };
            /*Disabling product start */
            $this->productIdsToReindex = [];
            $importer = $this->importerFactory->createImporter($config);
            $disableSkus = array_values(array_diff($allDickerdataSkus, $allSkus));
            foreach ($disableSkus as $lineNumber => $sku) {
                $product = new SimpleProduct($sku);
                $product->lineNumber = $lineNumber;
                $product->global()->setStatus(ProductStoreView::STATUS_DISABLED);
                $product->setIsDisabled(true);
                $importer->importSimpleProduct($product);
            }
            $importer->flush();
            /*Disabling product ends */

            /* Importing Products starts */
            $this->productIdsToReindex = [];
            $isFlush = false;
            $importer = $this->importerFactory->createImporter($config);

            $processResource = $this->processResourceFactory->create();
            $processResource->save($process);
            $i = 0; // Line number
            $j = 0; // Line number
            foreach ($items as $item) {
                $sku = trim($item[$this->headers[self::STOCK_CODE]]);
                try {
                    ++$i;
                    $this->start = microtime(true);

                    $this->processImport($item, $j, $importer, $since, $process);

                    $time = round(microtime(true) - $this->start, 2);
                    $isFlush = false;
                    if ($i % 5 === 0) {
                        $processResource->save($process);
                        /*if (!$isFlush) {
                            $this->start = microtime(true);
                            $importer->flush();
                            $importer = $this->importerFactory->createImporter($config);
                            $isFlush = true;
                        }*/
                    }
                } catch (WarningException $e) {
                    $message = __("Warning on sku %1: {$e->getMessage()}", $sku);
                    $process->output($message);
                } catch (\Exception $e) {
                    $error = __("Error for sku %1: {$e->getMessage()}", $sku);

                    $process->output($error);
                }
            }
        } catch (\Exception $e) {
            $process->fail($e->getMessage());
            throw $e;
        } finally {
            //if (!$isFlush) {
            $this->start = microtime(true);
            $importer->flush();
            //}
            // Reindex
            $process->output(__('Reindexing...'), true);
            $this->utilityHelper->reindexProducts($this->productIdsToReindex);
        }

        $process->output(__('Done!'));
    }
    private function processImport(&$data, &$j, &$importer, &$since, &$process)
    {
        $sku = trim($data[$this->headers[self::STOCK_CODE]]);

        $product = new SimpleProduct($sku);
        $product->lineNumber = $j + 1;

        $global = $product->global();
        $global->setStatus(ProductStoreView::STATUS_ENABLED);

        /* TODO: Adding price margin from this file app\code\Aalogics\Dropship\Model\Supplier\Xitdistribution.php*/
        /* Adding price starts*/
        $price = $data[$this->headers['RRPEx']];
        $price = floatval(preg_replace('/[^\d.]/', '', $price));
        $price = $this->utilityHelper->calcPriceMargin($price, 'dickerdata');
        if (isset($data[$this->headers['DealerEx']])) {
            $specialPrice = $data[$this->headers['DealerEx']];
            $specialPrice = $specialPrice ? floatval(preg_replace('/[^\d.]/', '', $specialPrice)) : null;
            $specialPrice = $this->utilityHelper->calcPriceMargin($specialPrice, 'dickerdata');
            if ($price > $specialPrice) {
                $global->setSpecialPrice($specialPrice);
            } else {
                $price = $specialPrice;
                $global->setSpecialPrice($price);
            }
        }
        $global->setPrice($price);
        /* Adding price ends*/

        /* Adding quantity starts*/
        $oldCategoryIds = [];
        $quantityFlag = false;
        $oldQuantities = [];
        if (isset($this->existingSkusWithSupplier[$sku])) {
            $currentProductData = $this->existingSkusWithSupplier[$sku];
            $sources = explode(",", $currentProductData['source_code']);
            $quantities = explode(",", $currentProductData['quantity']);
            $oldCategoryIds = array_unique(explode(",", $currentProductData['category_ids']));
            foreach ($sources as $k => $source) {
                $oldQuantities[$source] = $quantities[$k] ?? 0;
            }
        }
        $quantity = $data[$this->headers['StockAvailable']];
        $isBackOrder = false;
        if (in_array(strtolower($quantity), $this->backOrderValues)) {
            $isBackOrder = true;
        }
        $trimmedQty = trim($quantity, '+');
        if ($trimmedQty) {
            $quantity =  $trimmedQty;
        }
        $isInStock = $quantity > 0;
        $oldQuantity = $oldQuantities["default"] ?? "";
        if (!isset($oldQuantities["default"]) || (round($oldQuantity) - round($quantity)) != 0) {
            $quantityFlag = 1;
            $stock = $product->defaultStockItem();
            if ($isBackOrder) {
                $product->sourceItem("default")->setQuantity(0);
                $product->sourceItem("default")->setStatus(1);
                $stock->setQty(0);
                $stock->setIsInStock(1);
                $stock->setBackorders(ProductStockItem::BACKORDERS_ALLOW_QTY_BELOW_0_AND_NOTIFY_CUSTOMER);
                $stock->setUseConfigBackorders(false);
            } else {
                $product->sourceItem("default")->setQuantity($quantity);
                $product->sourceItem("default")->setStatus($isInStock);
                $stock->setQty($quantity);
                $stock->setIsInStock($isInStock);
                $stock->setMaximumSaleQuantity(10000.0000);
                $stock->setNotifyStockQuantity(1);
                $stock->setManageStock(true);
                $stock->setQuantityIncrements(1);
            }
        }

        /* Adding quantity ends */
        $categoryFlag = false;
        $categoryIds = [];
        $customAttrbutes = [];
        if ($data[$this->headers['Vendor']]) {
            $customAttrbutes['brand'] = strval($data[$this->headers['Vendor']]);
        }
        $categories = [];
        $categories[] = $data[$this->headers[Dickerdata::PRIMARY_CATEGORY]] ?? "";
        $categories[] = $data[$this->headers[Dickerdata::SECONDARY_CATEGORY]] ?? "";
        $lastCat = '';
        $level = 0;
        foreach (array_values($categories) as $categoryName) {
            if ($level <= 1) {
                $catName = strtolower($categoryName . self::SEPERATOR . $lastCat);
                $existingDickerdataCategoryIds = $this->existingDickerdataCategoryIds[$catName] ?? [];
                if ($existingDickerdataCategoryIds) {
                    $categoryIds = array_merge($categoryIds, explode(",", $existingDickerdataCategoryIds));
                }
                $useAsAttribute = $this->existingDickerdataCategoryAttributeIds[$catName] ?? "";
                if ($useAsAttribute) {
                    $customAttrbutes[$useAsAttribute] = $categoryName;
                }
            }
            $lastCat = $categoryName;
            if ($level > 1) {
                break;
            }
            $level++;
        }
        if ($categoryIds) {
            $categoryIds = array_filter(array_unique($categoryIds));
            if (array_diff($categoryIds, $oldCategoryIds)) {
                $categoryFlag = true;
                $product->addCategoryIds($categoryIds);
            }
        }
        if ($customAttrbutes) {
            foreach ($customAttrbutes as $attrbuteCode => $optionName) {
                if (isset($this->optionReplaceMents[$attrbuteCode][$optionName])) {
                    $optionId = $this->optionReplaceMents[$attrbuteCode][$optionName];
                    $global->setSelectAttributeOptionId($attrbuteCode, $optionId);
                } else {
                    $global->setSelectAttribute($attrbuteCode, $optionName);
                }
            }
        }

        if (isset($this->existingSkusWithSupplier[$sku])) {
            $supplier = $this->existingSkusWithSupplier[$sku]['supplier'] ?? "";
            $oldPrice = $this->existingSkusWithSupplier[$sku]['price'] ?? 0;
            if (($supplier != $this->supplierOptionId)) {
                if (round($price, 2) < round($oldPrice, 2)) {
                    $global->setSelectAttribute('supplier', self::SUPPLIER);
                } else {
                    unset($product);
                    return;
                }
            }
            if ($categoryFlag != true && $quantityFlag != true && (round($price, 2) - round($oldPrice, 2)) == 0) {
                unset($product);
                return;
            }
            $j++;
            $product->setIsUpdate(true);
            $importer->importSimpleProduct($product);
            return;
        }
        $name = $data[$this->headers['StockDescription']];
        $taxClassName = 'Taxable Goods';
        $attributeSetName = "Default";

        $product->setAttributeSetByName($attributeSetName);
        $product->setWebsitesByCode(['base']);

        $global->setName($name);
        $global->setPrice($price);
        $global->setTaxClassName($taxClassName);
        $global->setStatus(ProductStoreView::STATUS_ENABLED);
        $global->setVisibility(ProductStoreView::VISIBILITY_BOTH);
        $global->generateUrlKey();

        $global->setSelectAttribute('supplier', self::SUPPLIER);

        //brochure_url
        /*// German eav attributes
        $german = $product->storeView('de_store');
        $german->setName($line[3]);
        $german->setPrice($line[4]);*/

        $j++;
        $importer->importSimpleProduct($product);
    }
}
