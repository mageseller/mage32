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

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mageseller\IngrammicroImport\Logger\IngrammicroImport;
use Mageseller\Process\Model\Process;
use Mageseller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Mageseller\ProductImport\Api\Data\ProductStockItem;
use Mageseller\ProductImport\Api\Data\ProductStoreView;
use Mageseller\ProductImport\Api\Data\SimpleProduct;
use Mageseller\ProductImport\Api\ImportConfig;
use Mageseller\ProductImport\Api\ImporterFactory;

class ProductHelper extends AbstractHelper
{
    const SUPPLIER = 'ingrammicro';
    const SEPERATOR = " ---|--- ";
    const STOCK_CODE = 'Vendor Part Number';
    const STOCK_DESCRIPTION = 'StockDescription';

    /**
     * @var IngrammicroImport
     */
    protected $ingrammicroimportLogger;
    /**
     * @var ProcessResourceFactory
     */
    protected $processResourceFactory;
    /**
     * @var ImporterFactory
     */
    protected $importerFactory;
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
    protected $existingIngrammicroCategoryAttributeIds;

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
    protected $existingIngrammicroCategoryIds;
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
     * @param IngrammicroImport $ingrammicroimportLogger
     * @param ProcessResourceFactory $processResourceFactory
     * @param ImporterFactory $importerFactory
     * @param \Mageseller\Utility\Helper\Data $utilityHelper
     */
    public function __construct(
        Context $context,
        IngrammicroImport $ingrammicroimportLogger,
        ProcessResourceFactory $processResourceFactory,
        ImporterFactory $importerFactory,
        \Mageseller\Utility\Helper\Data $utilityHelper
    ) {
        parent::__construct($context);
        $this->ingrammicroimportLogger = $ingrammicroimportLogger;
        $this->processResourceFactory = $processResourceFactory;
        $this->importerFactory = $importerFactory;
        $this->utilityHelper = $utilityHelper;
    }

    public function processProducts($file, Process $process, $since, $sendReport = true)
    {
        try {
            /*
            Array
            (
                [Ingram Part Number] => 0
                [Ingram Part Description] => 1
                [Customer Part Number] => 2
                [Vendor Part Number] => 3
                [EANUPC Code] => 4
                [Plant] => 5
                [Vendor Number] => 6
                [Vendor Name] => 7
                [Size] => 8
                [Weight] => 9
                [Volume] => 10
                [Unit] => 11
                [Category ID] => 12
                [Customer Price] => 13
                [Retail Price] => 14
                [Availability Flag] => 15
                [Available Quantity] => 16
                [Backlog Information] => 17
                [Backlog ETA] => 18
                [License Flag] => 19
                [BOM Flag] => 20
                [Warranty Flag] => 21
                [Bulk Freight Flag] => 22
                [Material Long Description] => 23
                [Length] => 24
                [Width] => 25
                [Height] => 26
                [Dimension Unit] => 27
                [Weight Unit] => 28
                [Volume Unit] => 29
                [Category] => 30
                [Material Creation Reason code] => 31
                [Media Code] => 32
                [Material Language Code] => 33
                [Substitute Material] => 34
                [Superseded Material] => 35
                [Manufacturer Vendor Number] => 36
                [Sub-Category] => 37
                [Product Family] => 38
                [Purchasing Vendor] => 39
                [Material Change Code] => 40
                [Action code] => 41
                [Price Status] => 42
                [New Material Flag] => 43
                [Vendor Subrange] => 44
                [Case Qty] => 45
                [Pallet Qty] => 46
                [Direct Order identifier] => 47
                [Material Status] => 48
                [Discontinued / Obsoleted date] => 49
                [Release Date] => 50
                [Fulfilment type] => 51
                [Music Copyright Fees] => 52
                [Recycling Fees] => 53
                [Document Copyright Fees] => 54
                [Battery Fees] => 55
                [Customer Price with Tax] => 56
                [Retail Price with Tax] => 57
                [Tax Percent] => 58
                [Discount in Percent] => 59
                [Customer Reservation Number] => 60
                [Customer Reservation Qty] => 61
                [Agreement ID] => 62
                [Level ID] => 63
                [Period] => 64
                [Points] => 65
                [Company code] => 66
                [Company code Currency] => 67
                [Customer Currency Code] => 68
                [Customer Price Change Flag] => 69
                [Substitute Flag] => 70
                [Creation Reason Type] => 71
                [Creation Reason Value] => 72
                [Plant 01 Available Quantity] => 73
                [Plant 02 Available Quantity] => 74
            )
            */
            $allIngrammicroSkus = $this->utilityHelper->getAllSkus(self::SUPPLIER);
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
            $this->existingIngrammicroCategoryIds = $this->utilityHelper->getExistingCategoryIds('ingrammicro');
            $this->existingIngrammicroCategoryAttributeIds = $this->utilityHelper->getExistingCategoryAttributeIds('ingrammicro');
            $this->optionReplaceMents =  $this->utilityHelper->getOptionReplaceMents();
            $this->backOrderValues = $this->utilityHelper->getBackOrderValues('ingrammicro');

            $attributes = array_values($this->existingIngrammicroCategoryAttributeIds);
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
            $disableSkus = array_values(array_diff($allIngrammicroSkus, $allSkus));
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
        $supplierPartId = $data[$this->headers['Ingram Part Number']];
        $product = new SimpleProduct($sku);
        $product->lineNumber = $j + 1;

        $global = $product->global();
        $global->setStatus(ProductStoreView::STATUS_ENABLED);
        $global->setCustomAttribute('supplier_product_id', $supplierPartId);
        /* TODO: Adding price margin from this file app\code\Aalogics\Dropship\Model\Supplier\Xitdistribution.php*/
        /* Adding price starts*/
        $price = $data[$this->headers['Retail Price with Tax']];
        $price = floatval(preg_replace('/[^\d.]/', '', $price));
        $price = $this->utilityHelper->calcPriceMargin($price, 'ingrammicro');
        if (isset($data[$this->headers['Customer Price with Tax']])) {
            $specialPrice = $data[$this->headers['Customer Price with Tax']];
            $specialPrice = $specialPrice ? floatval(preg_replace('/[^\d.]/', '', $specialPrice)) : null;
            $specialPrice = $this->utilityHelper->calcPriceMargin($specialPrice, 'ingrammicro');
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
        $quantity = $data[$this->headers['Pallet Qty']];
        $isBackOrder = false;
        if (in_array(strtolower($quantity), $this->backOrderValues)) {
            $isBackOrder = true;
        } else {
            $trimmedQty = preg_replace("/[^0-9.]/", "", $quantity);
            if ($trimmedQty) {
                $quantity =  $trimmedQty;
            }
        }

        $isInStock = $quantity > 0;
        $oldQuantity = $oldQuantities["default"] ?? "";
        if (!isset($oldQuantities["default"]) || (round($oldQuantity) - round($quantity)) != 0) {
            $quantityFlag = true;
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
        if ($data[$this->headers['Vendor Name']]) {
            $customAttrbutes['brand'] = strval($data[$this->headers['Vendor Name']]);
        }
        $categories = [];
        if ($data[$this->headers[Ingrammicro::CATEGORY]]) {
            $categories[] = '1' . ltrim($data[$this->headers[Ingrammicro::CATEGORY]], '0');
        }
        if ($data[$this->headers[Ingrammicro::SUB_CATEGORY]]) {
            $categories[] = '2' . ltrim($data[$this->headers[Ingrammicro::SUB_CATEGORY]], '0');
        }
        if ($data[$this->headers[Ingrammicro::CATEGORY_ID]]) {
            $categories[] = '3' . ltrim($data[$this->headers[Ingrammicro::CATEGORY_ID]], '0');
        }
        foreach (array_values($categories) as $categoryName) {
            $existingIngrammicroCategoryIds = $this->existingIngrammicroCategoryIds[$categoryName] ?? [];
            if ($existingIngrammicroCategoryIds) {
                $categoryIds = array_merge($categoryIds, explode(",", $existingIngrammicroCategoryIds));
            }
            $useAsAttribute = $this->existingIngrammicroCategoryAttributeIds[$categoryName] ?? "";
            if ($useAsAttribute) {
                $customAttrbutes[$useAsAttribute] = $categoryName;
            }
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
        $name = $data[$this->headers['Ingram Part Description']];
        //$shortDescription = $data[$this->headers['Ingram Part Description']];
        $description = $data[$this->headers['Material Long Description']];

        $weight = round($data[$this->headers['Weight']], 2);
        $length = $data[$this->headers['Length']];
        $width = $data[$this->headers['Width']];
        $height = $data[$this->headers['Height']];
        //$warranty = $data[$this->headers['WARRANTY']];
        $taxClassName = 'Taxable Goods';
        $attributeSetName = "Default";

        $product->setAttributeSetByName($attributeSetName);
        $product->setWebsitesByCode(['base']);

        $global->setName($name);
        $global->setDescription($description);
        //$global->setShortDescription($shortDescription);
        $global->setTaxClassName($taxClassName);
        $global->setStatus(ProductStoreView::STATUS_ENABLED);
        $global->setVisibility(ProductStoreView::VISIBILITY_BOTH);
        $global->generateUrlKey();

        $global->setWeight($weight);
        $global->setCustomAttribute('ts_dimensions_height', $height);
        $global->setCustomAttribute('ts_dimensions_width', $width);
        $global->setCustomAttribute('ts_dimensions_length', $length);
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
