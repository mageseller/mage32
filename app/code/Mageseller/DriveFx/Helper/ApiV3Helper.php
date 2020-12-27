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
use Mageseller\DriveFx\Logger\DrivefxLogger;

/**
 * Class ApiHelper
 */
class ApiV3Helper extends ApiHelper
{
    private $baseUrl = "https://interface.phcsoftware.com/v3/";
    private $accessToken;

    /**
     * Data constructor.
     * @param Context $context
     * @param DrivefxLogger $drivefxlogger
     * @param Curl $curl
     */
    public function __construct(
        Context $context,
        DrivefxLogger $drivefxlogger,
        Curl $curl
    ) {
        parent::__construct($context, $drivefxlogger, $curl);
        $this->drivefxlogger = $drivefxlogger;
        $this->curlClient = $curl;
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
            $response = $this->driveFxRequest($url, $params);
            $this->accessToken = $response['token'] ?? "";
        }
        return $this->accessToken;
    }

    public function driveFxRequest($url, $params, $isJson = true)
    {
        if ($this->accessToken) {
            $this->curlClient->setOption(CURLOPT_HTTPHEADER, ["Authorization: " . $this->accessToken]);
        }
        if ($isJson) {
            $params = json_encode($params);
        }
        $this->curlClient->post($url, $params, false);
        $response = $this->curlClient->getBody();
        $response = json_decode($response, true);
        return $response;
    }

    public function searchEntities($entityName, $filterItem = '', $valueitem = '')
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
        $url = "$this->baseUrl/searchEntities";
        $response = $this->driveFxRequest($url, $request);
        return $response['entities'] ?? [];
    }
    public function getNewInstance($entity, $ndoc)
    {
        $request = [
            "entity" => $entity,
            "ndoc" => $ndoc
        ];

        $url = "$this->baseUrl/getNew";
        $response = $this->driveFxRequest($url, $request);
        return $response;
    }
    public function saveInstance($entity, $val, $ndoc)
    {
        $request = [
            "entity" => $entity,
            "ndoc" => $ndoc,
            "itemVO" => $val
        ];

        $url = "$this->baseUrl/saveInstance";
        $response = $this->driveFxRequest($url, $request);
        return $response;
    }
    public function updateInstance($entity, $val, $ndoc)
    {
        $val['Operation'] = 1;
        return $this->saveInstance($entity, $val, $ndoc);
    }
    public function getTypeOfInvoice()
    {
        if ($this->typeOfInvoices == null) {
            $this->typeOfInvoices = [];
            $response = $this->searchEntities($this->entity[parent::ALL_INVOICE], 'inactivo', 0);
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
    public function getCountryDetail($countryId)
    {
        if (isset($this->countryId[$countryId])) {
            return  $this->countryId[$countryId];
        }
        $countryResponse = $this->searchEntities('LocalizationWS', 'nomeabrv', $countryId);
        if ($countryResponse) {
            $pais = $countryResponse['entities'][0]['nome'] ?? '';
            $paisesstamp = $countryResponse['entities'][0]['paisesstamp'] ?? '';
            $this->countryId[$countryId] = [$pais,$paisesstamp];
        }
        return  $this->countryId[$countryId] ?? ['',''];
    }
    public function getCustomerParam($customerObject, $response)
    {
        $response['nome'] = $customerObject['name'];
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
    public function fetchEntity($entityName)
    {
        $request = [ "entity"  => $entityName ];
        $url = "$this->baseUrl/fetchRecords";
        $response = $this->driveFxRequest($url, $request);
        return $response;
    }
    public function createNewCustomer($customerObject)
    {
        $request =  [
            "customer" => [
                "name" => $customerObject['name'] ?? "",
                "address" => $customerObject['street'] ?? "",
                "postalCode" => $customerObject['postcode'] ?? "",
                "city" => $customerObject['city'] ?? "",
                "country" => $customerObject['country_id'] ?? "",
                "email" => $customerObject['email'] ?? "",
                "taxNumber" => $customerObject['taxNumber'] ?? "",
                "phone" => $customerObject['mobile'] ?? "",
                "mobilePhone" => $customerObject['mobile'] ?? "",
                "iban" => "",
                "bic" => "",
                "observations" => ""
            ]
        ];

        $url = "$this->baseUrl/createCustomer";
        $response = $this->driveFxRequest($url, $request);
        return $response;
    }
    public function createNewProduct($productObject)
    {
        $request =   [
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
        $response = $this->driveFxRequest($url, $request);
        return $response;
    }
    public function getClient($customerObject)
    {
        $clientEntityTable = $this->entity[parent::CLIENT];
        $email = is_array($customerObject) ? $customerObject['email'] : $customerObject;
        $response = $this->searchEntities($clientEntityTable, 'email', $email);
        $client = $response[0] ?? [];
        if ($client) {
            return  $client;
        } else {
            $response = $this->createNewCustomer($customerObject);
            $response = $this->searchEntities($clientEntityTable, 'email', $email);
            return $response[0] ?? [];
            /* $response = $this->getNewInstance($clientEntityTable, 1);
             $response = $this->getCustomerParam($customerObject, $response);
             $response['email'] = $customerObject['email'];
             $response['ncont'] = $customerObject['mobile'];
             echo "<pre>";
             print_r($response);
             die;
             $response = $this->saveInstance($clientEntityTable, $response, 1);*/
        }
        return $response;
    }
    public function getProductParam(array $productObject, $response)
    {
        $response['ref'] = $productObject['sku']; //reference of product
        $response['design'] = $productObject['name'] ?? 0; //name of product
        $response['epv1'] = $productObject['unitPrice'] ?? 0;    //retail price 1
        $response['iva1incl'] = $productObject['iva1incl'] ?? true;  //tax rate included
        $response['inactivo'] = $productObject['inactive'] ?? false; //activ
        return $response;
    }
    public function getProduct($productObject)
    {
        $productEntityTable = $this->entity[parent::PRODUCT];
        $ref = is_array($productObject) ? $productObject['sku'] : $productObject;
        $response = $this->searchEntities($productEntityTable, 'ref', $ref);
        $client = $response[0] ?? [];
        if ($client) {
            return  $client;
        } else {
            $response = $this->getNewInstance($productEntityTable, 1);
            $response = $this->getProductParam($productObject, $response);
            $this->saveInstance($productEntityTable, $response, 1);
            $response = $this->searchEntities($productEntityTable, 'ref', $ref);
        }
        return $response[0] ?? [];
    }

    public function createDocument($orderRequest)
    {
        $this->generateAccessToken();
        $customerObject = $orderRequest['customer'] ?? [];
        $customer = $this->getClient($customerObject);
        $this->writeToLog($customer, "CustomerId");

        //  $customer['email'] = $customer['email'];
        //$this->writeToLog($customer, "ProductRef");
        //$response = $this->updateInstance($this->entity[parent::CLIENT],$customer,1);
        //echo "<pre>";
        //print_r($customer);
        //die;

        $customerId = $customer['no'] ?? "";
        $requestWarehouse = [];
        $request =  [
            "customer" => [
                "number" => $customerId,
                "name" => $customerObject['name'] ?? "Generaric Client",
                "address" => $customerObject['street'] ?? "Generaric Street",
                "postalCode" => $customerObject['postcode'] ?? "",
                "city" => $customerObject['city'] ?? "",
                "email" => $customerObject['email'] ?? "",
                "country" => "PT",
                "taxNumber" => $customerObject['taxNumber'] ?? "",
            ]
        ];
        //$requestWarehouse['customer'] = $request['customer'];
        $request['requestOptions'] =  [
            "option" => 1,
            "requestedFields" =>["fno", "ftstamp", "etotal", "ettiva"]
        ];
        $requestWarehouse['requestOptions'] = $request['requestOptions'];
        //$requestWarehouse['requestOptions']['requestedFields'] = [ "no","obranome"];
        $requestWarehouse['requestOptions']['reportName']  = "Minimal Customer Order";
        $request['document'] = [
            "docType" => 1,
            "customerNumber" => $customerId,
           /* "customerName" => $customerObject['name'],
            "invoicingAddress1" => $customerObject['shipping_street'] ?? $customerObject['street'] ?? "",
            "invoicingPostalCode" => $customerObject['shipping_postcode'] ?? $customerObject['postcode'] ?? "",
            "invoicingLocality" => $customerObject['shipping_city'] ?? $customerObject['city'] ?? "",
            "documentObservations" => "Document Observations"*/
        ];
        $requestWarehouse['internalDocument'] = $request['document'];
        $requestWarehouse['internalDocument']["docType"] = 5;
        $requestWarehouse['internalDocument']["documentObservations"] = "Warehouse transfer via API";

        $request['products'] = [];
        $products = $orderRequest['products'] ?? [];
        foreach ($products as $key => $product) {
            $productRef = $this->getProduct($product);
            $productRef['stock'] = intval($product['qty']);
            $productRef['qttfor'] = intval($product['qty']);
            $productRef['qttcli'] = intval($product['qty']);
            $productRef['epv1'] = floatval($product['unitPrice']);
            $this->writeToLog($productRef, "ProductRef");
            $response = $this->updateInstance($this->entity[parent::PRODUCT],$productRef,1);
            $this->writeToLog($response, "UpdateProduct");
            $productArray = [
                "reference" => $product['sku'],
                "designation" => $product['name'],
                "unitPrice" => floatval($product['unitPrice']),
                "quantity" => intval($product['qty']),
            ];
            $request['products'][] = $productArray;
            $productArray["warehouse"] = 1;
            $productArray["targetWarehouse"] = 2;
            $requestWarehouse['products'][] = $productArray;
        }

        /*echo "<pre>";
        echo json_encode($request);die;*/
        /*echo "<pre>";
        print_r($requestWarehouse);
        die;*/

        /*$url = "$this->baseUrl/createInternalDocument";
        $response = $this->driveFxRequest($url, $requestWarehouse);
        $this->writeToLog($response, "createInternalDocument");*/
       /* echo "<pre>";
        print_r($response);
        die;*/

        $url = "$this->baseUrl/createDocument";
        $response = $this->driveFxRequest($url, $request);
        $this->writeToLog($response, "createDocument");

        print_r($response);
        die;
    }
}
