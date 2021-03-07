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

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mageseller\LeadersystemsImport\Logger\LeadersystemsImport;
use Mageseller\Process\Model\Process;
use Mageseller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Mageseller\ProductImport\Api\Data\ProductStockItem;
use Mageseller\ProductImport\Api\Data\ProductStoreView;
use Mageseller\ProductImport\Api\Data\SimpleProduct;
use Mageseller\ProductImport\Api\ImportConfig;
use Mageseller\ProductImport\Api\ImporterFactory;

class ProductHelper extends AbstractHelper
{
    const SUPPLIER = 'leadersystems';
    const SEPERATOR = " ---|--- ";
    const STOCK_CODE = 'STOCK CODE';
    const STOCK_DESCRIPTION = 'StockDescription';

    /**
     * @var LeadersystemsImport
     */
    protected $leadersystemsimportLogger;
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
    protected $existingLeadersystemsCategoryAttributeIds;

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
    protected $existingLeadersystemsCategoryIds;
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
     * @var array
     */
    private $allAttirbutesOptions;
    /**
     * @var array
     */
    private $existingSkusCategoriesWithSupplier;

    /**
     * @param Context $context
     * @param LeadersystemsImport $leadersystemsimportLogger
     * @param ProcessResourceFactory $processResourceFactory
     * @param ImporterFactory $importerFactory
     * @param \Mageseller\Utility\Helper\Data $utilityHelper
     */
    public function __construct(
        Context $context,
        LeadersystemsImport $leadersystemsimportLogger,
        ProcessResourceFactory $processResourceFactory,
        ImporterFactory $importerFactory,
        \Mageseller\Utility\Helper\Data $utilityHelper
    ) {
        parent::__construct($context);
        $this->leadersystemsimportLogger = $leadersystemsimportLogger;
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
                [STOCK CODE] => 0
                [CATEGORY CODE] => 1
                [CATEGORY NAME] => 2
                [SUBCATEGORY NAME] => 3
                [SHORT DESCRIPTION] => 4
                [LONG DESCRIPTION] => 5
                [BAR CODE] => 6
                [DBP] => 7
                [RRP] => 8
                [IMAGE] => 9
                [MANUFACTURER] => 10
                [MANUFACTURER SKU] => 11
                [WEIGHT] => 12
                [LENGTH] => 13
                [WIDTH] => 14
                [HEIGHT] => 15
                [WARRANTY] => 16
                [AT] => 17
                [AA] => 18
                [AQ] => 19
                [AN] => 20
                [AV] => 21
                [AW] => 22
                [ETAA] => 23
                [ETAQ] => 24
                [ETAN] => 25
                [ETAV] => 26
                [ETAW] => 27
                [ALTERNATIVE REPLACEMENTS] => 28
                [OPTIONAL ACCESSORIES] => 29
            )*/
            $allLeadersystemsSkus = $this->utilityHelper->getAllSkus(self::SUPPLIER);
            $allSkus = [];

            $this->headers = array_flip($file->readCsv());

            $items = [];
            while (false !== ($row = $file->readCsv())) {
                $items[] = $row;
                $allSkus[] = trim($row[$this->headers[self::STOCK_CODE]]);
            }
            $option = $this->utilityHelper->loadOptionValues('supplier');
            $this->supplierOptionId = $option[self::SUPPLIER] ?? "";
            $this->existingLeadersystemsCategoryAttributeIds = $this->utilityHelper->getExistingCategoryAttributeIds('leadersystems');
            $attributes = array_values($this->existingXitCategoryAttributeIds);
            $attributes[] = "brand";
            $attributes = array_unique($attributes);
            $this->allAttirbutesOptions = $this->utilityHelper->getAllAttirbutesOptions($attributes);
            $this->existingSkusWithSupplier = $this->utilityHelper->getExistingSkusWithSupplier($allSkus,$attributes);
            $this->existingSkusCategoriesWithSupplier = $this->utilityHelper->getExistingSkusCategoriesWithSupplier($allSkus);
            //$this->existingSkus = $this->utilityHelper->getExistingSkus($allSkus);
            $this->existingLeadersystemsCategoryIds = $this->utilityHelper->getExistingCategoryIds('leadersystems');

            $this->optionReplaceMents =  $this->utilityHelper->getOptionReplaceMents();
            $this->backOrderValues = $this->utilityHelper->getBackOrderValues('leadersystems');

            $config = new ImportConfig();
            $attributes[] = "supplier";
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
            $disableSkus = array_values(array_diff($allLeadersystemsSkus, $allSkus));
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
                $sku = $item[$this->headers[self::STOCK_CODE]];
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
        $price = $data[$this->headers['RRP']];
        $price = round(preg_replace('/[^\d.]/', '', $price), 2);
        $price = $this->utilityHelper->calcPriceMargin($price, 'leadersystems');
        if (isset($data[$this->headers['DBP']])) {
            $specialPrice = $data[$this->headers['DBP']];
            $specialPrice = $specialPrice ? round(preg_replace('/[^\d.]/', '', $specialPrice), 2) : null;
            $specialPrice = $this->utilityHelper->calcPriceMargin($specialPrice, 'leadersystems');
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
            $currentProductCategoryData = $this->existingSkusCategoriesWithSupplier[$sku];
            $oldCategoryIds = array_unique(explode(",", $currentProductCategoryData['category_ids']));
            foreach ($sources as $k => $source) {
                $oldQuantities[$source] = $quantities[$k] ?? 0;
            }
        }
        $quantities = [];

        /*
         * AA = SA
         * AW = WA
         * AQ = QLD
         * AN = NSW
         * AV = VIC
         * */
        $quantities['sa'] = $data[$this->headers['AT']];
        $quantities['wa'] = $data[$this->headers['AW']];
        $quantities['qld'] = $data[$this->headers['AQ']];
        $quantities['nsw'] = $data[$this->headers['AN']];
        $quantities['vic'] = $data[$this->headers['AV']];
        $stock = $product->defaultStockItem();
        foreach ($quantities as $stockType => $quantity) {
            if (in_array(strtolower($quantity), $this->backOrderValues)) {
                $isInStock = true;
                $quantity = 0;
                $stock->setBackorders(ProductStockItem::BACKORDERS_ALLOW_QTY_BELOW_0_AND_NOTIFY_CUSTOMER);
                $stock->setUseConfigBackorders(false);
            } else {
                $trimmedQty = preg_replace("/[^0-9.]/", "", $quantity);
                if ($trimmedQty) {
                    $quantity =  $trimmedQty;
                }
                $isInStock = $quantity > 0;
            }
            $oldQuantity = $oldQuantities[$stockType] ?? "";
            if (!isset($oldQuantities[$stockType]) || (round($oldQuantity) - round($quantity)) != 0) {
                $quantityFlag = 1;
                $product->sourceItem($stockType)->setQuantity($quantity);
                $product->sourceItem($stockType)->setStatus($isInStock);
                //$stock->setQty($quantity);
                $stock->setIsInStock($isInStock);
                $stock->setMaximumSaleQuantity(10000.0000);
                $stock->setNotifyStockQuantity(1);
                $stock->setManageStock(true);
                $stock->setQuantityIncrements(1);
            }
        }

        /* $isInStock = $quantity > 0;
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
         }*/
        /* Adding quantity ends */

        $categoryFlag = false;
        $categoryIds = [];
        $customAttrbutes = [];
        if ($data[$this->headers['MANUFACTURER']]) {
            $customAttrbutes['brand'] = strval($data[$this->headers['MANUFACTURER']]);
        }
        $categories = [];
        $categories[] = $data[$this->headers[Leadersystems::PRIMARY_CATEGORY]] ?? "";
        $categories[] = $data[$this->headers[Leadersystems::SECONDARY_CATEGORY]] ?? "";
        $lastCat = '';
        $level = 0;
        foreach (array_values($categories) as $categoryName) {
            if ($level <= 1) {
                $catName = strtolower($categoryName . self::SEPERATOR . $lastCat);
                $existingLeadersystemsCategoryIds = $this->existingLeadersystemsCategoryIds[$catName] ?? [];
                if ($existingLeadersystemsCategoryIds) {
                    $categoryIds = array_merge($categoryIds, explode(",", $existingLeadersystemsCategoryIds));
                }
                $useAsAttribute = $this->existingLeadersystemsCategoryAttributeIds[$catName] ?? "";
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
        $optionFlag = false;
        if ($customAttrbutes) {
            foreach ($customAttrbutes as $attrbuteCode => $optionName) {
                if (isset($this->optionReplaceMents[$attrbuteCode][$optionName])) {
                    $optionId = $this->optionReplaceMents[$attrbuteCode][$optionName];
                    $global->setSelectAttributeOptionId($attrbuteCode, $optionId);
                } else {
                    $oldOptionValue =  $this->existingSkusWithSupplier[$sku][$attrbuteCode] ?? "";
                    $currentOptionValue = $this->allAttirbutesOptions[$attrbuteCode][strtolower($optionName)] ?? "";
                    if($oldOptionValue != $currentOptionValue || $currentOptionValue == ""){
                        $optionFlag = true;
                        if( $currentOptionValue ){
                            $global->setSelectAttributeOptionId($attrbuteCode, $currentOptionValue);
                        } else {
                            $global->setSelectAttribute($attrbuteCode, $optionName);
                        }
                    }
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
            if ($optionFlag != true && $categoryFlag != true && $quantityFlag != true && (round($price, 2) - round($oldPrice, 2)) == 0) {
                unset($product);
                return;
            }
            $j++;
            $product->setIsUpdate(true);
            $importer->importSimpleProduct($product);
            return;
        }
        $name = $data[$this->headers['SHORT DESCRIPTION']];
        $shortDescription = $data[$this->headers['SHORT DESCRIPTION']];
        $description = $data[$this->headers['LONG DESCRIPTION']];

        $weight = round($data[$this->headers['WEIGHT']], 2);
        $length = $data[$this->headers['LENGTH']];
        $width = $data[$this->headers['WIDTH']];
        $height = $data[$this->headers['HEIGHT']];
        $warranty = $data[$this->headers['WARRANTY']];
        $taxClassName = 'Taxable Goods';
        $attributeSetName = "Default";

        $product->setAttributeSetByName($attributeSetName);
        $product->setWebsitesByCode(['base']);

        $global->setName($name);
        $global->setDescription($description);
        $global->setShortDescription($shortDescription);
        $global->setPrice($price);
        $global->setTaxClassName($taxClassName);
        $global->setStatus(ProductStoreView::STATUS_ENABLED);
        $global->setVisibility(ProductStoreView::VISIBILITY_BOTH);
        $global->generateUrlKey();

        $global->setWeight(round($weight, 2));
        $global->setCustomAttribute('ts_dimensions_height', round($height, 2));
        $global->setCustomAttribute('ts_dimensions_width', round($width, 2));
        $global->setCustomAttribute('ts_dimensions_length', round($length, 2));
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
