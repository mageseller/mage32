<?php
/*
 * A Magento 2 module named Mageseller/DriveFx
 * Copyright (C) 2020
 *
 *  @author      satish29g@hotmail.com
 *  @site        https://www.mageseller.com/
 *
 * This file included in Mageseller/DriveFx is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 *
 */

namespace Mageseller\DriveFx\Helper;

use Magento\Framework\App\Helper\Context;
use Mageseller\DriveFx\HTTP\Client\Curl;
use Mageseller\DriveFx\HTTP\Client\V3Curl;
use Mageseller\DriveFx\Logger\DrivefxLogger;

/**
 * Class ApiHelper
 */
class ApiV3Helper extends ApiHelper
{
    const DRIVEFX_SUPPLIER_NAME = 'drivefx/supplier/name';
    const DRIVEFX_SUPPLIER_EMAIL = 'drivefx/supplier/email';
    const DRIVEFX_SUPPLIER_CONTACT = 'drivefx/supplier/contact';
    const DRIVEFX_GENERAL_V_3_URL = 'drivefx/general/v3url';
    private $baseUrl = "https://interface.phcsoftware.com/v3/";
    private $accessToken;
    /**
     * @var V3Curl
     */
    private $v3CurlClient;
    /**
     * @var EavAtrributeUpdateHelper
     */
    private $eavAtrributeUpdateHelper;

    /**
     * Data constructor.
     * @param Context $context
     * @param DrivefxLogger $drivefxlogger
     * @param Curl $curl
     * @param EavAtrributeUpdateHelper $eavAtrributeUpdateHelper
     * @param V3Curl $v3url
     */
    public function __construct(
        Context $context,
        DrivefxLogger $drivefxlogger,
        Curl $curl,
        EavAtrributeUpdateHelper $eavAtrributeUpdateHelper,
        V3Curl $v3url
    )
    {
        parent::__construct($context, $drivefxlogger, $curl);
        $this->drivefxlogger = $drivefxlogger;
        $this->v3CurlClient = $v3url;
        $this->eavAtrributeUpdateHelper = $eavAtrributeUpdateHelper;

        $this->baseUrl = rtrim($this->getApiV3Url(), '/');
    }

    public function getApiV3Url()
    {
        return $this->getConfig(self::DRIVEFX_GENERAL_V_3_URL);
    }

    public function getTypeOfInvoice()
    {
        if ($this->typeOfInvoices == null) {
            $this->typeOfInvoices = [];
            $response = $this->searchV3Entities($this->entity[parent::ALL_INVOICE], 'inactivo', 0);
            if ($response) {
                foreach ($response as $value) {
                    if ($value['tiposaft'] == 'FT' || $value['tiposaft'] == 'FS' || $value['tiposaft'] == 'FR') {
                        $this->typeOfInvoices[] = [
                            "ndoc" => $value['ndoc'],
                            "nmdoc" => $value['nmdoc'],
                        ];
                    }
                }
            }
        }
        return $this->typeOfInvoices;
    }

    public function searchV3Entities($entityName, $filterItem = '', $valueitem = '', $customFilter = [])
    {
        $filterItems = $filterItem ? [
            [
                "filterItem" => $filterItem,
                "comparison" => 0,
                "valueItem" => $valueitem
            ]
        ] : [];
        $request = [
            "queryObject" => [
                "entityName" => $entityName,
                "filterItems" => $filterItems
            ]
        ];
        if ($customFilter) {
            $request["queryObject"] = array_merge($request["queryObject"], $customFilter);
        }

        $url = "$this->baseUrl/searchEntities";
        $response = $this->driveFxV3Request($url, $request);
        return $response['entities'] ?? [];
    }

    public function driveFxV3Request($url, $params, $isJson = true)
    {
        if ($this->accessToken) {
            $this->v3CurlClient->setOption(CURLOPT_HTTPHEADER, ["Authorization: " . $this->accessToken]);
        }
        if ($isJson) {
            $params = json_encode($params);
        }
        $this->v3CurlClient->post($url, $params, false);
        $response = $this->v3CurlClient->getBody();
        $response = json_decode($response, true);
        return $response;
    }

    public function fetchV3Entity($entityName)
    {
        $request = ["entity" => $entityName];
        $url = "$this->baseUrl/fetchRecords";
        $response = $this->driveFxV3Request($url, $request);
        return $response;
    }

    public function createNewProduct($productObject)
    {
        $request = [
            "product" => [
                "reference" => $productObject['sku'] ?? "",
                "designation" => $productObject['name'] ?? "",
                "unitCode" => "M",
                "family" => "Aluguer",
                "category" => 2,
                "unitPrice1" => 15.55,
                "taxIncluded1" => true,
                "tecDescription" => "its a tec description",

            ]
        ];

        $url = "$this->baseUrl/createProduct";
        $response = $this->driveFxV3Request($url, $request);
        return $response;
    }

    public function getCustomerParam($customerObject, $response)
    {
        $taxNumber = $customerObject['taxNumber'] ?? "702214825";
        $response['nome'] = $customerObject['name'];
        $response['email'] = $customerObject['email'];
        $response['ncont'] = $taxNumber ? $taxNumber : "702214825";
        if (isset($customerObject['street'])) {
            $response['morada'] = $customerObject['street'] ?? "";
            $response['local'] = $customerObject['city'] ?? "";
            $response['provincia'] = $customerObject['city'] ?? "";
            $response['codpost'] = $customerObject['postcode'] ?? "";
            $response['telefone'] = $customerObject['mobile'] ?? "";

            $response['moradato'] = $customerObject['shipping_street'] ?? "";
            $response['localto'] = $customerObject['shipping_city'] ?? "";
            $response['codpostto'] = $customerObject['shipping_postcode'] ?? "";
            //$response['Operation'] = 2;
            $countryId = $customerObject['country_id'];
            if ($countryId) {
                list($pais, $paisesstamp) = $this->getCountryDetail($countryId);
                $response['pais'] = $pais;
                $response['paisesstamp'] = $paisesstamp;
            }

            $shippingCountryId = $customerObject['shipping_country_id'] ?? "";
            if ($shippingCountryId) {
                list($paisto, $paisesstampto) = $this->getCountryDetail($shippingCountryId);
                $response['paisto'] = $paisto;
                $response['paisesstampto'] = $paisesstampto;
            }
        }

        return $response;
    }

    public function getCountryDetail($countryId)
    {
        if (isset($this->countryId[$countryId])) {
            return $this->countryId[$countryId];
        }
        $countryResponse = $this->searchV3Entities('LocalizationWS', 'nomeabrv', $countryId);
        if ($countryResponse) {
            $pais = $countryResponse['entities'][0]['nome'] ?? '';
            $paisesstamp = $countryResponse['entities'][0]['paisesstamp'] ?? '';
            $this->countryId[$countryId] = [$pais, $paisesstamp];
        }
        return $this->countryId[$countryId] ?? ['', ''];
    }

    public function createDocument($orderRequest)
    {
        $this->generateAccessToken();
        $orderId = $orderRequest['order']['entity_id'] ?? "";
        $customerObject = $orderRequest['customer'] ?? [];
        $customer = $orderRequest['customerObject'] ?? [];

        $customerId = $customer ? $customer->getData('drive_fx_customer_id') : "";
        if (!$customerId) {
            $_customer = $this->getClient($customerObject);
            $customerId = $_customer['no'] ?? "";
            if ($customerId) {
                $customer->setData('drive_fx_customer_id', $customerId)->save();
            }
        }
        $supplier = $this->getSupplier();
        $supplierId = $supplier['no'] ?? "";
        $requestWarehouse = [];
        $taxPayerNUmber = $customerObject['taxNumber'] ?? "";
        if ($this->hasValidRule($taxPayerNUmber)) {
            if (!$this->hasValidLength($taxPayerNUmber)) {
                $taxPayerNUmber = "";
            }

            if (!$this->hasValidPattern($taxPayerNUmber)) {
                $taxPayerNUmber = "";
            }
        } else {
            $taxPayerNUmber = "";
        }
        $request = [
            "customer" => [
                "name" => $customerObject['name'] ?? "Generaric Client",
                "address" => $customerObject['street'] ?? "Generaric Street",
                "postalCode" => $customerObject['postcode'] ?? "",
                "city" => $customerObject['city'] ?? "",
                "email" => $customerObject['email'] ?? "",
                "country" => "PT",
                "taxNumber" => $taxPayerNUmber,
            ]
        ];
        if ($customerId) {
            $request['customer']['number'] = (int)$customerId;
        }
        $requestWarehouse['customer'] = $request['customer'];
        $request['requestOptions'] = [
            "option" => 0,
            "requestedFields" => ["fno", "ftstamp", "etotal", "ettiva"],
            "reportName" => "Minimal Customer Invoice"
        ];
        $requestWarehouse['requestOptions'] = [
            "option" => 0,
            "requestedFields" => ["obrano", "no", "obranome"],
            "reportName" => "Minimal Customer Order"
        ];
        //$requestWarehouse['requestOptions']['requestedFields'] = [ "no","obranome"];
        //$requestWarehouse['requestOptions']['reportName']  = "Minimal Customer Order";
        $request['document'] = [
            "docType" => 1,
            "customerName" => $customerObject['name'] ?? "Generaric Client",
            "salesmanName" => $this->getSupplierName(),
            "invoicingAddress1" => $customerObject['shipping_street'] ?? $customerObject['street'] ?? "",
            "invoicingPostalCode" => $customerObject['shipping_postcode'] ?? $customerObject['postcode'] ?? "",
            "invoicingLocality" => $customerObject['shipping_city'] ?? $customerObject['city'] ?? "",
            "documentObservations" => "Invoice Document"
        ];
        if ($customerId) {
            $request['document']['customerNumber'] = (int)$customerId;
        }

        $requestWarehouse['internalDocument'] = [
            "docType" => 1,
            "customerName" => $customerObject['name'] ?? "Generaric Client",
            "salesmanName" => $this->getSupplierName(),
            "description" => "Order",
            "issuingAddress1" => "Issue Address 1",
            "issuingPostalCode" => "2010-152",
            "issuingLocality" => "Issue locality",
            "documentObservations" => "This is an observation"
        ];
        if ($customerId) {
            $requestWarehouse['internalDocument']['customerNumber'] = (int)$customerId;
        }
        if ($supplierId) {
            $requestWarehouse['internalDocument']['supplierNumber'] = (int)$supplierId;
        }
        $orderId = $this->getLastV3OrderId();
        if ($orderId) {
            $requestWarehouse['internalDocument']['number'] = $orderId + 1;
        }
        $request['products'] = [];
        $products = $orderRequest['products'] ?? [];
        foreach ($products as $key => $product) {
            $productObject = $product['productObjects'] ?? "";
            if (!$productObject->getData('productObjects')) {
                $productRef = $this->getProduct($product);
                if ($productRef) {
                    $this->eavAtrributeUpdateHelper->updateProductAttributes([$productObject->getId()], [
                        'drivefx_ref_id' => $productRef['ref']
                    ], $productObject->getStoreId());
                }
            }

            $productStock = $this->getProductStock($product);
            $currentQty = $productStock['qtt'] ?? 0;
            $stock = $productStock['stock'] ?? 0;
            $sastock = $productStock['sastock'] ?? 0;
            if ($currentQty < intval($product['qty']) + 1 || $stock < intval($product['qty']) + 1 || $sastock < intval($product['qty']) + 1) {
                $productStock['qtt'] = intval($product['qty']) + 1;
                $productStock['stock'] = intval($product['qty']) + 1;
                $productStock['sastock'] = intval($product['qty']) + 1;
                $response = $this->updateV3Instance($this->entity[parent::PRODUCTSTOCK], $productStock, 1);
            }
            /*$productRef['stock'] = intval($product['qty']);
            $productRef['qttfor'] = intval($product['qty']);
            $productRef['qttcli'] = intval($product['qty']);
            $productRef['epv1'] = floatval($product['unitPrice']);
            $this->writeToLog($productRef, "ProductRef");
            $response = $this->updateV3Instance($this->entity[parent::PRODUCT], $productRef, 1);
            $this->writeToLog($response, "UpdateProduct");*/
            $productArray = [
                'reference' => $product['sku'],
                'designation' => $product['name'],
                'unitPrice' => floatval($product['unitPrice']),
                'quantity' => intval($product['qty']),
                'warehouse' => 1,
                'taxIncluded' => true,
                'taxPercentage' => intval($product['taxPercentage']),
                'taxRegion' => 'PT',
            ];
            if ($product['discount']) {
                $productArray['discount1'] = floatval($product['discount']);
            }
            $request['products'][] = $productArray;
            $productArray['tax'] = 'tax';
            $productArray['unitCode'] = 'M';
            $requestWarehouse['products'][] = $productArray;
            /*$requestWarehouse['products'][] = array_merge($productArray, [
                'tax' => 'tax',
                'taxIncluded' => true,
                'taxPercentage' => $product['taxPercentage'],
                'taxRegion' => 'PT',
            ]);*/
        }
        $order = $orderRequest['orderObject'];
        if (!$order->getData('bodata_reposnse')) {
            $url = "$this->baseUrl/createInternalDocument";
            $response = $this->driveFxV3Request($url, $requestWarehouse);
            $code = $response['code'] ?? 1;
            if ($code == 0) {
                $obrano = $response['requestedFields']['obrano'] ?? '';
                $order->setData('bodata_reposnse', $obrano);
            }
            $order->addStatusToHistory(false, json_encode($response));
        }
        if (!$order->getData('invoice_response')) {
            $url = "$this->baseUrl/createDocument";
            $response = $this->driveFxV3Request($url, $request);
            $code = $response['code'] ?? 1;
            if ($code == 0) {
                $fno = $response['requestedFields']['fno'] ?? '';
                $order->setData('invoice_response', $fno);
            }
            $order->addStatusToHistory(false, json_encode($response));
        }
        $order->save();
    }

    public function generateAccessToken()
    {
        if ($this->accessToken == null) {
            $this->urlBase = $this->getBaseUrl();
            $this->username = $this->getUsername();
            $this->password = $this->getPassword();
            $this->appType = $this->getAppType();
            $this->company = $this->getCompany();
            $params = [
                "credentials" => [
                    "backendUrl" => $this->urlBase,
                    "appId" => $this->appType,
                    "userCode" => $this->username,
                    "password" => $this->password,
                    "company" => "",
                    "tokenLifeTime" => "NEVER"
                ]
            ];

            $url = "$this->baseUrl/generateAccessToken";
            $response = $this->driveFxV3Request($url, $params);
            $this->accessToken = $response['token'] ?? "";
        }

        return $this->accessToken;
    }

    public function getClient($customerObject)
    {
        $clientEntityTable = $this->entity[parent::CLIENT];
        $email = is_array($customerObject) ? $customerObject['email'] : $customerObject;
        $response = $this->searchV3Entities($clientEntityTable, 'email', $email, [
            "limit" => 1,
            "orderByItems" => [
                [
                    "AggregateType" => 0,
                    "fieldStruc" => [
                    ],
                    "OrderItem" => "no",
                    "OrderType" => 1
                ]
            ], "SelectItems" => ["no"]
        ]);
        $client = $response[0] ?? [];

        if ($client) {
            return $client;
        } else {
            $response = $this->createNewCustomer($customerObject);
            $response = $this->searchV3Entities($clientEntityTable, 'email', $email, [
                "limit" => 1,
                "orderByItems" => [
                    [
                        "AggregateType" => 0,
                        "fieldStruc" => [
                        ],
                        "OrderItem" => "no",
                        "OrderType" => 1
                    ]
                ], "SelectItems" => ["no"]
            ]);
            return $response[0] ?? [];

            /*$response = $this->getNewV3Instance($clientEntityTable, 1);
            $response = $this->getCustomerParam($customerObject, $response);
            $response = $this->saveV3Instance($clientEntityTable, $response, 1);
            $response = $this->searchV3Entities($clientEntityTable, 'email', $email);*/
        }
        return $response;
    }

    public function createNewCustomer($customerObject)
    {
        /*$taxNumber = $customerObject['taxNumber'] ?? "233936688";
        $taxNumber = $taxNumber ? $taxNumber : "233936688";*/
        // $customerObject['country_id'] = "UK";
        $countryId = $customerObject['country_id'] ?? "PT";
        $countryResponse = $this->searchV3Entities('Country', "pncont", $customerObject['country_id']);

        if (!$countryResponse) {
            $countryResponse = $this->searchV3Entities('Country', "nomeabrv", $customerObject['country_id']);
        }
        $countryId = $countryResponse[0]['nomeabrv'] ?? "PT";
        $request = [
            "customer" => [
                "name" => $customerObject['name'] ?? "",
                "address" => $customerObject['street'] ?? "",
                "postalCode" => $customerObject['postcode'] ?? "",
                "city" => $customerObject['city'] ?? "",
                "country" => $countryId,
                "email" => $customerObject['email'] ?? "",
                "taxNumber" => $customerObject['taxNumber'] ?? "",
                "phone" => $customerObject['mobile'] ?? "",
                "mobilePhone" => $customerObject['mobile'] ?? "",
            ]
        ];
        $url = "$this->baseUrl/createCustomer";
        $response = $this->driveFxV3Request($url, $request);
        return $response;
    }

    public function getSupplier()
    {
        $supplierEntityTable = $this->entity[parent::SUPPLIER];
        $email = $this->getSupplierEmail();
        $response = $this->searchV3Entities($supplierEntityTable, 'email', $email);
        $supplier = $response[0] ?? [];
        if ($supplier) {
            return $supplier;
        } else {
            $response = $this->getNewV3Instance($supplierEntityTable, 1);
            $response = $this->getSupplierParam($response);
            $this->saveV3Instance($supplierEntityTable, $response, 1);
            $response = $this->searchV3Entities($supplierEntityTable, 'email', $email);
        }
        return $response[0] ?? [];
    }

    public function getSupplierEmail()
    {
        return $this->getConfig(self::DRIVEFX_SUPPLIER_EMAIL);
    }

    public function getNewV3Instance($entity, $ndoc)
    {
        $request = [
            "entity" => $entity,
            "ndoc" => $ndoc
        ];

        $url = "$this->baseUrl/getNew";
        $response = $this->driveFxV3Request($url, $request);
        return $response;
    }

    private function getSupplierParam($response)
    {
        $response['nome'] = $this->getSupplierName();
        $response['email'] = $this->getSupplierEmail();
        $response['ncont'] = $this->getSupplierContact();
        return $response;
    }

    public function getSupplierName()
    {
        return $this->getConfig(self::DRIVEFX_SUPPLIER_NAME);
    }

    public function getSupplierContact()
    {
        return $this->getConfig(self::DRIVEFX_SUPPLIER_CONTACT);
    }

    public function saveV3Instance($entity, $val, $ndoc)
    {
        $request = [
            "entity" => $entity,
            "ndoc" => $ndoc,
            "itemVO" => $val
        ];

        $url = "$this->baseUrl/saveInstance";
        $response = $this->driveFxV3Request($url, $request);
        return $response;
    }

    protected function hasValidRule(string $tin): bool
    {
        $c1 = $this->digitAt($tin, 0);
        $c2 = $this->digitAt($tin, 1);
        $c3 = $this->digitAt($tin, 2);
        $c4 = $this->digitAt($tin, 3);
        $c5 = $this->digitAt($tin, 4);
        $c6 = $this->digitAt($tin, 5);
        $c7 = $this->digitAt($tin, 6);
        $c8 = $this->digitAt($tin, 7);
        $c9 = $this->digitAt($tin, 8);
        $sum = $c1 * 9 + $c2 * 8 + $c3 * 7 + $c4 * 6 + $c5 * 5 + $c6 * 4 + $c7 * 3 + $c8 * 2;
        $remainderBy11 = $sum % 11;
        $checkDigit = 11 - $remainderBy11;

        if (9 >= $checkDigit) {
            return $checkDigit === $c9;
        }

        if (10 === $checkDigit) {
            return 0 === $c9;
        }

        return 0 === $c9;
    }

    protected function digitAt(string $str, int $index): int
    {
        return (int)($str[$index] ?? 0);
    }

    protected function hasValidLength(string $tin): bool
    {
        return $this->matchLength($tin, 9);
    }

    protected function matchLength(string $tin, int $length): bool
    {
        return mb_strlen($tin) === $length;
    }

    protected function hasValidPattern(string $tin): bool
    {
        return $this->matchPattern($tin, '\\d{9}');
    }

    protected function matchPattern(string $subject, string $pattern): bool
    {
        return 1 === preg_match(sprintf('/%s/', $pattern), $subject);
    }

    public function getLastV3OrderId()
    {
        $response = $this->searchV3Entities('Bo', "", "", [
            "limit" => 1,
            "orderByItems" => [
                [
                    "AggregateType" => 0,
                    "fieldStruc" => [
                    ],
                    "OrderItem" => "obrano",
                    "OrderType" => 1
                ]
            ], "SelectItems" => ["obrano"]
        ]);
        return $response[0]['obrano'] ?? "0";
    }

    public function getProduct($productObject)
    {
        $productEntityTable = $this->entity[parent::PRODUCT];
        $ref = is_array($productObject) ? $productObject['sku'] : $productObject;
        $response = $this->searchV3Entities($productEntityTable, 'ref', $ref);
        $client = $response[0] ?? [];
        if ($client) {
            return $client;
        } else {
            $response = $this->getNewV3Instance($productEntityTable, 1);
            $response = $this->getProductParam($productObject, $response);
            $this->saveV3Instance($productEntityTable, $response, 1);
            $response = $this->searchV3Entities($productEntityTable, 'ref', $ref);
        }
        return $response[0] ?? [];
    }

    public function getProductParam(array $productObject, $response)
    {
        $response['ref'] = $productObject['sku']; //reference of product
        $response['design'] = $productObject['name'] ?? 0; //name of product
        $response['epv1'] = $productObject['unitPrice'] ?? 0;    //retail price 1
        $response['iva1incl'] = $productObject['iva1incl'] ?? true;  //tax rate included
        $response['inactivo'] = $productObject['inactive'] ?? false; //activ
        $isVirtual = $productObject['is_virtual'] ?? false;
        $response['stns'] = $isVirtual ? true : false; //activ

        return $response;
    }

    public function getProductStock($productObject)
    {
        $productStockEntityTable = $this->entity[parent::PRODUCTSTOCK];
        $ref = is_array($productObject) ? $productObject['sku'] : $productObject;
        $filter['filterItems'] = [
            [
                "filterItem" => 'ref',
                "comparison" => 0,
                "valueItem" => $ref
            ],
            [
                "filterItem" => 'cm',
                "comparison" => 0,
                "valueItem" => 5
            ]
        ];
        $response = $this->searchV3Entities($productStockEntityTable, '', '', $filter);
        $client = $response[0] ?? [];
        if ($client) {
            return $client;
        } else {
            $response = $this->getNewV3Instance($productStockEntityTable, 1);
            $response = $this->getStockProductParam($productObject, $response);
            $res = $this->saveV3Instance($productStockEntityTable, $response, 1);
            $response = $this->searchV3Entities($productStockEntityTable, 'ref', $ref);
        }
        return $response[0] ?? [];
    }

    public function getStockProductParam(array $productObject, $response)
    {
        $productStock = $productObject['stock_qty'] ?? 10;
        $response['ref'] = $productObject['sku']; //reference of product
        $response['design'] = $productObject['name'] ?? 0; //name of product
        $response['cm'] = 5;
        $response['cmdesc'] = "Stock Inicial";
        $response['qtt'] = $productStock > 0 ? $productStock : 10;
        $response["movdescription"] = "Stock Inicial";
        $response["Cm2LabelField"] = "5 - Stock Inicial (Entrada)";
        return $response;
    }

    public function updateV3Instance($entity, $val, $ndoc)
    {
        $val['Operation'] = 1;
        return $this->saveV3Instance($entity, $val, $ndoc);
    }
}
