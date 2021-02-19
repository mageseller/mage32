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

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mageseller\Process\Model\Process;
use Mageseller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Mageseller\ProductImport\Api\Data\ProductStockItem;
use Mageseller\ProductImport\Api\Data\ProductStoreView;
use Mageseller\ProductImport\Api\Data\SimpleProduct;
use Mageseller\ProductImport\Api\ImportConfig;
use Mageseller\ProductImport\Api\ImporterFactory;
use Mageseller\XitImport\Logger\XitImport;

class ProductHelper extends AbstractHelper
{
    const SUPPLIER = 'xitdistribution';
    const SEPERATOR = " ---|--- ";

    /**
     * @var XitImport
     */
    protected $xitimportLogger;
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
    protected $existingXitCategoryAttributeIds;

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
    protected $existingXitCategoryIds;
    /**
     * @var array
     */
    protected $existingSkus;


    /**
     * @param Context $context
     * @param XitImport $xitimportLogger
     * @param ProcessResourceFactory $processResourceFactory
     * @param ImporterFactory $importerFactory
     * @param \Mageseller\Utility\Helper\Data $utilityHelper
     */
    public function __construct(
        Context $context,
        XitImport $xitimportLogger,
        ProcessResourceFactory $processResourceFactory,
        ImporterFactory $importerFactory,
        \Mageseller\Utility\Helper\Data $utilityHelper
    ) {
        parent::__construct($context);
        $this->xitimportLogger = $xitimportLogger;
        $this->processResourceFactory = $processResourceFactory;
        $this->importerFactory = $importerFactory;
        $this->utilityHelper = $utilityHelper;
    }

    public function processProducts($items, Process $process, $since, $sendReport = true)
    {
        try {
            $allXitSkus = $this->utilityHelper->getAllSkus(self::SUPPLIER);
            $allSkus = [];
            foreach ($items as $item) {
                $allSkus[] = (string)$item->ItemDetail->ManufacturerPartID;
            }
            $this->existingSkusWithSupplier = $this->utilityHelper->getExistingSkusWithSupplier($allSkus);
            $this->existingSkus = $this->utilityHelper->getExistingSkus($allSkus);
            $this->existingXitCategoryIds = $this->utilityHelper->getExistingCategoryIds('xit');
            $this->existingXitCategoryAttributeIds = $this->utilityHelper->getExistingCategoryAttributeIds('xit');
            $this->optionReplaceMents =  $this->utilityHelper->getOptionReplaceMents();
            $this->backOrderValues = $this->utilityHelper->getBackOrderValues('xit');

            $attributes = array_values($this->existingXitCategoryAttributeIds);
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
                    $message = sprintf("%s: success! sku = %s, id = %s  ( $time s)\n", $product->lineNumber, $product->getSku(), $product->id);
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
            $disableSkus = array_values(array_diff($allXitSkus, $allSkus));
            foreach ($disableSkus as $lineNumber => $sku) {
                $product = new SimpleProduct($sku);
                $product->lineNumber = $lineNumber;
                $product->global()->setStatus(ProductStoreView::STATUS_DISABLED);
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
                $sku = (string)$item->ItemDetail->ManufacturerPartID;
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
                } catch (Exception $e) {
                    $error = __("Error for sku %1: {$e->getMessage()}", $sku);

                    $process->output($error);
                }
            }
            /* Importing Products ends */
        } catch (Exception $e) {
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

        $sku = (string) $data->ItemDetail->ManufacturerPartID;
        $taxRate = (string) $data->ItemDetail->TaxRate;
        $updatedAt = (string) $data->UpdatedAt;
        $currentUpdateAT = date_parse($updatedAt);
        $oldUpdateAt = date_parse($since->format('Y-m-d H:i:s'));

        $product = new SimpleProduct($sku);
        $product->lineNumber = $j + 1;



        $global = $product->global();
        $global->setStatus(ProductStoreView::STATUS_ENABLED);

        /* TODO: Adding price margin from this file app\code\Aalogics\Dropship\Model\Supplier\Xitdistribution.php*/

        /* Adding price starts*/
        $price = floatval(preg_replace('/[^\d.]/', '', strval($data->ItemDetail->UnitPrice)));
        if (isset($data->ItemDetail->RRP)) {
            $price = floatval(preg_replace('/[^\d.]/', '', strval($data->ItemDetail->RRP)));
            $specialPrice = floatval(preg_replace('/[^\d.]/', '', strval($data->ItemDetail->UnitPrice)));
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
        $availibilities = $data->Availability->Warehouse;
        $quantity = 0;
        $isBackOrder = false;
        foreach ($availibilities as $avail) {
            $v = (string)$avail->StockLevel;
            if (in_array(strtolower($v), $this->backOrderValues)) {
                $isBackOrder = true;
                continue;
            }
            $trimmedQty = trim($v, '+');
            if ($trimmedQty) {
                $quantity =  $trimmedQty;
                $isBackOrder = false;
            }
        }
        $isInStock = $quantity > 0;

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

        /* Adding quantity ends */

        if (isset($this->existingSkus[$sku])) {
            //if ($oldUpdateAt <= $currentUpdateAT) {
            $j++;
            $importer->importSimpleProduct($product);
            //}
            return;
        }
        $categoryIds = [];
        $customAttrbutes = [];
        if (isset($data->ItemDetail->ManufacturerName)) {
            $customAttrbutes['brand'] = strval($data->ItemDetail->ManufacturerName);
        }
        $categories = $this->utilityHelper->parseObject($data->ItemDetail->Classifications->Classification);
        unset($categories['@attributes']);
        $lastCat = '';
        $level = 0;
        foreach (array_values($categories) as $categoryName) {
            $catName = strtolower($categoryName . self::SEPERATOR . $lastCat);
            if ($level <= 2) {
                $existingXitCategoryIds = $this->existingXitCategoryIds[$catName] ?? [];
                if ($existingXitCategoryIds) {
                    $categoryIds = array_merge($categoryIds, explode(",", $existingXitCategoryIds));
                }
                $useAsAttribute = $this->existingXitCategoryAttributeIds[$catName] ?? "";
                if ($useAsAttribute) {
                    $customAttrbutes[$useAsAttribute] = $categoryName;
                }
            } else {
                //$customAttrbutes[$useAsAttribute] = $categoryName;
            }

            $lastCat = $categoryName;
            if ($level > 2) {
                break;
            }
            $level++;
        }
        if ($categoryIds) {
            $categoryIds = array_filter(array_unique($categoryIds));
            $product->addCategoryIds($categoryIds);
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
        $name = (string)$data->ItemDetail->Title;
        $description = (string)$data->ItemDetail->Description;
        $taxClassName = 'Taxable Goods';
        $attributeSetName = "Default";

        $product->setAttributeSetByName($attributeSetName);
        $product->setWebsitesByCode(['base']);

        $global->setName($name);
        $global->setDescription($description);
        $global->setPrice($price);
        $global->setTaxClassName($taxClassName);
        $global->setStatus(ProductStoreView::STATUS_ENABLED);
        $global->setVisibility(ProductStoreView::VISIBILITY_BOTH);
        $global->generateUrlKey();

        if (isset($data->ItemDetail->ShippingWeight)) {
            $weight = (string)$data->ItemDetail->ShippingWeight;
            $global->setWeight($weight);
        }
        if (isset($data->ItemDetail->Height)) {
            $height = (string)$data->ItemDetail->Height;
            $global->setCustomAttribute('ts_dimensions_height', $height);
        }
        if (isset($data->ItemDetail->Width)) {
            $width = (string)$data->ItemDetail->Width;
            $global->setCustomAttribute('ts_dimensions_width', $width);
        }
        if (isset($data->ItemDetail->Length)) {
            $length = (string)$data->ItemDetail->Length;
            $global->setCustomAttribute('ts_dimensions_length', $length);
        }
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
