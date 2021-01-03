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

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Mageseller\DriveFx\HTTP\Client\Curl;
use Mageseller\DriveFx\Logger\DrivefxLogger;

/**
 * Class ApiHelper
 */
class ApiHelper extends AbstractHelper
{
    const DRIVEFX_GENERAL_ENABLE = 'drivefx/general/enable';
    const DRIVEFX_GENERAL_URL = 'drivefx/general/url';
    const DRIVEFX_GENERAL_USERNAME = 'drivefx/general/username';
    const DRIVEFX_GENERAL_PASSWORD = 'drivefx/general/password';
    const DRIVEFX_GENERAL_APP_TYPE = 'drivefx/general/app_type';
    const DRIVEFX_GENERAL_COMPANY = 'drivefx/general/company';
    const DRIVEFX_GENERAL_DEBUG = 'drivefx/general/debug';
    const CLIENT = 'Client';
    const SUPPLIER = 'Supplier';
    const PRODUCT = 'Product';
    const PRODUCTSTOCK = 'Product_Stock';
    const INVOICE = 'Invoice';
    const ALL_INVOICE = 'AllInvoice';
    const BO = 'Bo';
    /**
     * @var DrivefxLogger
     */
    protected $drivefxlogger;
    protected $curlClient;
    protected $table = [
        self::CLIENT => 'ClWS',
        self::SUPPLIER => 'FlWS',
        self::PRODUCT => 'StWS',
        self::PRODUCTSTOCK => 'SlWS',
        self::INVOICE => 'FtWS',
        self::BO => 'BoWS',
        'TsWS','TsWS'
    ];
    protected $entity = [
        self::CLIENT => 'Cl',
        self::SUPPLIER => 'Fl',
        self::PRODUCT => 'St',
        self::PRODUCTSTOCK => 'Sl',
        self::INVOICE => 'Ft',
        self::ALL_INVOICE => 'Td',
        self::BO => self::BO,
    ];
    protected $urlBase;
    protected $username;
    protected $password;
    protected $appType;
    protected $company;
    protected $isLogin;
    protected $typeOfInvoices;
    protected $_globalData;
    protected $countryId;
    protected $customeUrl = "";

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
        parent::__construct($context);
        $this->drivefxlogger = $drivefxlogger;
        $this->curlClient = $curl;
    }

    /**
     * @return mixed
     */
    public function getEnable()
    {
        return $this->getConfig(self::DRIVEFX_GENERAL_ENABLE);
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

    public function writeToErrorLog($info)
    {
        if ($this->isEnableDebug()) {
            $this->drivefxlogger->error($info);
        }
    }

    /**
     * @return mixed
     */
    public function isEnableDebug()
    {
        return $this->getConfig(self::DRIVEFX_GENERAL_DEBUG);
    }

    public function makeLogout()
    {
        /*******************************************************************
         *          Called webservice that makes logout of Drive FX         *
         ********************************************************************/
        $url = $this->customeUrl . "REST/UserLoginWS/userLogout";
        $this->curlClient->get($url);
        $this->isLogin = false;
    }

    public function getStockList()
    {
        $response = $this->queryAsEntities($this->entity[self::PRODUCT], '', '', [
            'OrderItem' => 'familia',
            'OrderType' => 1
        ]);
        return $response['result'];
    }

    public function queryAsEntities($entityNameTable, $filterItem = '', $valueItem = '', $arrayFilter = [])
    {
        $selectItems = $arrayFilter['SelectItems'] ?? [];
        $groupByItems = $arrayFilter['groupByItems'] ?? [];
        $joinEntities = $arrayFilter['joinEntities'] ?? [];
        $orderByItems = $arrayFilter['orderByItems'] ?? [];
        $filterCod = $arrayFilter['filterCod'] ?? '';
        $offset = $arrayFilter['offset'] ?? 0;
        $limit = $arrayFilter['limit'] ?? 50;
        $filterItems = $filterItem ? [
            0 => [
                'comparison' => 0,
                'filterItem' => $filterItem,
                'valueItem' => $valueItem,
                'groupItem' => 1,
                'checkNull' => false,
                'skipCheckType' => false,
                'type' => 'Number',
            ],
        ] : [];
        $url = $this->customeUrl . "REST/SearchWS/QueryAsEntities";
        $itemQuery = [
            'groupByItems' => $groupByItems,
            'lazyLoaded' => false,
            'joinEntities' => $joinEntities,
            'orderByItems' => $orderByItems,
            'SelectItems' => $selectItems,
            'entityName' => $entityNameTable,
            'filterItems' => $filterItems
        ];

        if ($filterCod) {
            $itemQuery['filterCod'] = $filterCod;
        }
        if ($offset) {
            $itemQuery['offset'] = $offset;
        }
        if ($limit) {
            $itemQuery['limit'] = $limit;
        }

        $params = [
            'itemQuery' => json_encode($itemQuery)
        ];
        return $this->driveFxRequest($url, $params);
    }

    public function driveFxRequest($url, $params)
    {

        $this->curlClient->post($url, $params, false);
        $response = $this->curlClient->getBody();
        $response = json_decode($response, true);
        return $response;
    }

    public function getClientList()
    {
        $response = $this->queryAsEntities($this->entity[self::CLIENT]);
        return $response['result'];
    }

    public function getSupplierList()
    {
        $response = $this->queryAsEntities($this->entity[self::SUPPLIER]);
        return $response['result'];
    }

    public function getBoList()
    {
        $url = $this->customeUrl . "REST/BoWS/getAllRecords";
        return $this->driveFxRequest($url, []);
        $response = $this->queryAsEntities($this->entity[self::BO]);
        return $response['result'];
    }

    public function getAllInvoiceList()
    {
        $response = $this->queryAsEntities($this->entity[self::ALL_INVOICE]);
        return $response['result'];
    }

    public function obtainInvoices()
    {
        if (!$this->typeOfInvoices) {
            /*******************************************************************
             * Called webservice that obtain all invoice documents (FT, FS, FR) *
             ********************************************************************/
            $response = $this->queryAsEntities($this->entity[self::ALL_INVOICE], 'inactivo', 0);
            return $response;
            $this->typeOfInvoices = [];
            if ($response) {
                foreach ($response['result'] as $value) {
                    if ($value['tiposaft'] == 'FT' || $value['tiposaft'] == 'FS' || $value['tiposaft'] == 'FR') {
                        $this->typeOfInvoices[] = [
                            "ndoc" => $value['ndoc'],
                            "nmdoc" => $value['nmdoc'],
                        ];
                    }
                }
            }
        }

        $html = "<form method='post' action=''>";
        $html .= "Choose type of invoice document that you want generate:<br><br><select id='typeOfInvoices' name='typeOfInvoices'>";
        $html .= "<option value='0'>Select one...</option>";
        foreach ($this->typeOfInvoices as $typeOfInvoice) {
            $html .= "<option value=" . $typeOfInvoice['ndoc'] . ">" . $typeOfInvoice['nmdoc'] . "</option><br>";
        }
        $html .= "</select>";
        $html .= "<br><br><br><input type='submit' name='generate_ft' value='Generate'>";
        $html .= "</form>";
        return $html;
    }

    public function checkClientExist($orderRequest)
    {
        if ($this->makeLogin()) {
            $email = $orderRequest['customer']['email'];
            /************************************************************************
             *        Called webservice that find if client already exists           *
             *************************************************************************/
            $response = $this->queryAsEntities($this->entity[self::CLIENT], 'email', $email);

            if (!$response) {
                $response = $this->createNewClient($orderRequest);
                if ($response) {
                    return $this->_globalData['number_client'] = $response['result'][0]['no'];
                }
            }
        }
        return false;
    }

    public function makeLogin()
    {
        if ($this->isLogin == null) {
            /*************************************************************
             *         Called webservice that make login in Drive FX       *
             *************************************************************/
            $this->initCurl();
            $url = $this->customeUrl . "REST/UserLoginWS/userLoginCompany";
            // Create map with request parameters
            $params = [
                'userCode' => $this->username,
                'password' => $this->password,
                'applicationType' => $this->appType,
                'company' => $this->company
            ];
            $this->curlClient->post($url, $params);
            $response = $this->curlClient->getBody();
            if ($response) {
                $this->isLogin = $response;
            } else {
                $this->isLogin = false;
            }
        }
        return $this->isLogin;
    }

    public function initCurl()
    {
        $this->urlBase = $this->getBaseUrl();
        $this->username = $this->getUsername();
        $this->password = $this->getPassword();
        $this->appType = $this->getAppType();
        $this->company = $this->getCompany();
        $this->customeUrl = $this->urlBase . "/PHCWS/";


        $this->curlClient->setOption(CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/32.0.1700.107 Chrome/32.0.1700.107 Safari/537.36');
        $this->curlClient->setOption(CURLOPT_POST, true);
        $this->curlClient->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curlClient->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curlClient->setOption(CURLOPT_COOKIESESSION, true);
        $this->curlClient->setOption(CURLOPT_COOKIEJAR, '');
        $this->curlClient->setOption(CURLOPT_COOKIEFILE, '');
        return $this->curlClient;
    }

    /**
     * @return mixed
     */
    public function getBaseUrl()
    {
        return $this->getConfig(self::DRIVEFX_GENERAL_URL);
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->getConfig(self::DRIVEFX_GENERAL_USERNAME);
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->getConfig(self::DRIVEFX_GENERAL_PASSWORD);
    }

    /**
     * @return mixed
     */
    public function getAppType()
    {
        return $this->getConfig(self::DRIVEFX_GENERAL_APP_TYPE);
    }

    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->getConfig(self::DRIVEFX_GENERAL_COMPANY);
    }

    /*public function getNewInstanceByRef()
    {
        $this->getNewInstanceByRef('FtWS', 'addNewFIsByRef', 'IdFtStamp', 'fiStampEditing', $response['result'][0]['ftstamp'], $_SESSION['listOfSku']);
    }*/
    public function getCountryDetail($countryId)
    {
        if (isset($this->countryId[$countryId])) {
            return  $this->countryId[$countryId];
        }
        $countryResponse = $this->queryAsEntities('LocalizationWS', 'nomeabrv', $countryId);
        if ($countryResponse) {
            $pais = $countryResponse['result'][0]['nome'] ?? '';
            $paisesstamp = $countryResponse['result'][0]['paisesstamp'] ?? '';
            $this->countryId[$countryId] = [$pais,$paisesstamp];
        }
        return  $this->countryId[$countryId] ?? ['',''];
    }
    public function getProductStock($productRequest)
    {
        if ($isLogin = $this->makeLogin()) {
            echo "<pre>";
            echo $this->table[self::CLIENT];
            $response = $this->getNewInstance("SlWS", 0);
            print_r($response);
            die;
        }
    }
    public function createNewClient($orderRequest)
    {
        if ($isLogin = $this->makeLogin()) {

            /************************************************************************
             *        Called webservice that obtain a new instance of client         *
             *************************************************************************/
            $response = $this->getNewInstance($this->table[self::CLIENT], 0);
            if ($response) {
                //Change name and email of client
                $response = $this->getCustomerParam($orderRequest, $response);
                $response['result'][0]['email'] = $orderRequest['customer']['email'];
                $response['result'][0]['ncont'] = $orderRequest['customer']['mobile'];

                /************************************************************************
                 *                    Called webservice that save client                 *
                 *************************************************************************/
                $response = $this->saveEntity($this->table[self::CLIENT], $response['result'][0]);
                if ($response) {
                    return $response;
                }
                return false;
            }
        }
    }

    public function getNewInstance($entity, $ndos)
    {
        $url = $this->customeUrl . "REST/$entity/getNewInstance";
        $params = ['ndos' => $ndos];
        return $this->driveFxRequest($url, $params);
    }

    public function saveEntity($entity, $paramObject, $runWarningRules = 'false')
    {
        $url = $this->customeUrl . "REST/$entity/Save";
        $params = [
            'itemVO' => json_encode($paramObject),
            'runWarningRules' => $runWarningRules
        ];
        return $this->driveFxRequest($url, $params);
    }

    public function checkSupplierExist(string $email, $name, $mobile)
    {
        if ($this->makeLogin()) {
            /************************************************************************
             *        Called webservice that find if supplier already exists           *
             *************************************************************************/
            $response = $this->queryAsEntities($this->entity[self::SUPPLIER], 'email', $email);
            if (!$response) {
                $response = $this->createNewSupplier($email, $name, $mobile);
            }
            if ($response) {
                if (is_array($response['result']) && !empty($response['result'][0])) {
                    return $this->_globalData['number_supplier'] = $response['result'][0]['no'];
                }
            }
            return false;
        }
    }

    public function createNewSupplier($email, $name, $mobile)
    {
        /************************************************************************
         *        Called webservice that obtain a new instance of client         *
         *************************************************************************/
        $response = $this->getNewInstance($this->table[self::SUPPLIER], 0);

        if ($response) {
            //Change name and email of supplier
            $response['result'][0]['nome'] = $name; //'Fornecedor de Teste - Exemplo';
            $response['result'][0]['email'] = $email; //'fornecedor@phc.pt';
            $response['result'][0]['ncont'] = $mobile; //'987654321';
            /************************************************************************
             *                    Called webservice that save supplier                 *
             *************************************************************************/
            $response = $this->saveEntity($this->table[self::SUPPLIER], $response['result'][0]);

            if ($response) {
                return $response;
            }
            return false;
        }
    }

    public function checkProductExist(string $sku, $name, $price)
    {
        if ($this->makeLogin()) {
            /***********************************************************************
             *        Called webservice that find  if product already exists        *
             ************************************************************************/
            $response = $this->queryAsEntities($this->entity[self::PRODUCT], 'ref', $sku);
            if (!$response) {
                $response = $this->createNewProduct2($sku, $name, $price);
            }
            if ($response) {
                if (is_array($response['result']) && !empty($response['result'][0])) {
                    return $response['result'][0]['ref'];
                }
            }
            return false;
        }
    }

    public function createNewProduct2($sku, $name, $price, $taxInclude = true, $inactive = false)
    {
        /************************************************************************
         *        Called webservice that obtain a new instance of product        *
         *************************************************************************/
        $response = $this->getNewInstance($this->table[self::PRODUCT], 0);

        if ($response) {
            $response['result'][0]['ref'] = $sku; //reference of product
            $response['result'][0]['design'] = $name; //name of product

            $response['result'][0]['epv1'] = $price;    //retail price 1
            $response['result'][0]['iva1incl'] = $taxInclude;  //tax rate included
            $response['result'][0]['inactivo'] = $inactive; //active

            /************************************************************************
             *                   Called webservice that save product                 *
             *************************************************************************/
            $response = $this->saveEntity($this->table[self::PRODUCT], $response['result'][0]);
            if ($response) {
                return $response;
            }
            return false;
        }
    }

    public function getNewInstanceByRef($entity, $webserviceMethod, $fieldWs, $fieldWsEditing, $stamp, $listOfRefs)
    {
        $url = $this->customeUrl . "REST/$entity/$webserviceMethod";
        $params = [
            $fieldWs => $stamp,
            'refsIds' => json_encode($listOfRefs),
            $fieldWsEditing => ""
        ];
        return $this->driveFxRequest($url, $params);
    }
    public function activateBo($stamp_bo, $nr_client)
    {
        $url = $this->customeUrl . "REST/BoWS/ActBo";
        // Create map with request parameters
        $params = [
            'IdBoStamp' => $stamp_bo,
            'codigo' => 'no',
            'newValue' => json_encode([$nr_client,0])
        ];
        return $this->driveFxRequest($url, $params);
    }
    public function activateEntity($entity, $paramObject, $code = 0)
    {
        $url = $this->customeUrl . "REST/$entity/actEntity";
        $params = [
            'entity' => json_encode($paramObject),
            'code' => $code,
            'newValue' => json_encode([])
        ];
        return $this->driveFxRequest($url, $params);
    }

    public function signDocument($entity, $ftstamp)
    {
        $url = $this->customeUrl . "REST/$entity/signDocument";
        $params = [
            'ftstamp' => $ftstamp
        ];
        return $this->driveFxRequest($url, $params);
    }

    /**
     * @param $info
     */
    public function writeToLog($info, $comment = "")
    {
        if ($this->isEnableDebug()) {
            $info = is_array($info) || is_object($info) ? json_decode(json_encode($info), true) : $info;
            if ($comment) {
                $info = "$comment : " . print_r($info, true);
            }
            $this->drivefxlogger->info($info);
        }
    }

    public function getReportForPrint($entityName, $numdoc)
    {
        $url = $this->customeUrl . "REST/reportws/getReportsForPrint";
        $params = [
            'entityname' => $entityName,
            'numdoc' => $numdoc
        ];
        return $this->driveFxRequest($url, $params);
    }

    public function getCustomerParam($orderRequest, $response)
    {
        $response['result'][0]['nome'] = $orderRequest['customer']['name'];
        $response['result'][0]['morada'] = $orderRequest['customer']['street'];
        $response['result'][0]['local'] = $orderRequest['customer']['city'];
        $response['result'][0]['provincia'] = $orderRequest['customer']['city'];
        $response['result'][0]['codpost'] = $orderRequest['customer']['postcode'];
        $response['result'][0]['telefone'] = $orderRequest['customer']['mobile'];

        $response['result'][0]['moradato'] = $orderRequest['customer']['shipping_street'] ?? "";
        $response['result'][0]['localto'] = $orderRequest['customer']['shipping_city'] ?? "";
        $response['result'][0]['codpostto'] = $orderRequest['customer']['shipping_postcode'] ?? "";
        //$response['result'][0]['Operation'] = 2;
        $countryId = $orderRequest['customer']['country_id'];
        list($pais, $paisesstamp) = $this->getCountryDetail($countryId);
        $shippingCountryId = $orderRequest['customer']['shipping_country_id'] ?? "";
        list($paisto, $paisesstampto) = $this->getCountryDetail($shippingCountryId);

        $response['result'][0]['pais'] = $pais;
        $response['result'][0]['paisesstamp'] = $paisesstamp;
        $response['result'][0]['paisto'] = $paisto;
        $response['result'][0]['paisesstampto'] = $paisesstampto;
        return $response;
    }
    public function getProductRefs($products)
    {
        $productRefs = [];
        foreach ($products as $product) {
            $sku = $product['sku'];
            $productName = $product['name'];
            $productPrice = $product['price'];
            $productRefs[] = $this->checkProductExist($sku, $productName, $productPrice);
        }
        return $productRefs;
    }
    public function addNewOrder($orderRequest)
    {
        if ($clientId = $this->checkClientExist($orderRequest)) {
            $incrementId = $orderRequest['order']['increment_id'];
            $response = $this->queryAsEntities($this->table[self::BO], "bostamp", $incrementId);
            if (empty($response['result'][0])) {
                //Obtain new instance of Bo
                $response = $this->getNewInstance($this->table[self::BO], 2);

                $products = $orderRequest['products'] ?? [];
                $productRefs = $this->getProductRefs($products);
                if ($response) {
                    //Obtain VO with updated Bo
                    $response = $this->activateBo($response['result'][0]['bostamp'], $this->_globalData['number_client']);
                    if ($response) {
                        //Obtain VO with updated Bo
                        $response = $this->getNewInstanceByRef('BoWS', 'addNewBIsByRef', 'IdBoStamp', 'biStampEditing', $response['result'][0]['bostamp'], $productRefs);
                        if ($response) {
                            $response = $this->getCustomerParam($orderRequest, $response);

                            //Associate client to FT
                            $response['result'][0]['no'] = $this->_globalData['number_client'];
                            foreach ($products as $key => $product) {
                                //Qty in line of product
                                $response['result'][0]['bis'][$key]['qtt'] = $product['qty'];
                                //Price in line of product
                                $response['result'][0]['bis'][$key]['ettdeb'] = $product['price'];

                                //Remove discounts
                                $response['result'][0]['bis'][$key]['desconto'] = 0;

                                $response['result'][0]['bis'][$key]['desc2'] = 0;
                                $response['result'][0]['bis'][$key]['desc3'] = 0;
                                $response['result'][0]['bis'][$key]['desc4'] = 0;
                                $response['result'][0]['bis'][$key]['desc5'] = 0;
                                $response['result'][0]['bis'][$key]['desc6'] = 0;

                                //Eliminate financial discount of client
                                $response['result'][0]['bis'][$key]['efinv'] = 0;
                                $response['result'][0]['bis'][$key]['fin'] = 0;
                            }
                            //If VO yet exists
                            if (isset($response['result'][0])) {
                                $this->saveEntity($this->entity[self::BO], $response);
                                $response = $response['result'][0];
                                $this->_globalData['responseBo'] = $response;
                            }
                        }
                    }
                }
            }
        }
    }
    public function generateInvoice($orderRequest, $typeOfInvoices = 'FT')
    {
        if ($clientId = $this->checkClientExist($orderRequest)) {
            /************************************************************************
             *           Called webservice that obtain a new instance of FT          *
             *************************************************************************/
            $response = $this->getNewInstance($this->table[self::INVOICE], $typeOfInvoices);

            if ($response) {
                $ftStamp = $response['result'][0]['ftstamp'];
                $products = $orderRequest['products'] ?? [];
                $productRefs = $this->getProductRefs($products);

                /******************************************************************************
                 *                      Add new line to the invoice document                   *
                 *******************************************************************************/
                $response = $this->getNewInstanceByRef(
                    'FtWS',
                    'addNewFIsByRef',
                    'IdFtStamp',
                    'fiStampEditing',
                    $ftStamp,
                    $productRefs
                );

                $response = $this->getCustomerParam($orderRequest, $response);

                //Associate client to FT
                $response['result'][0]['no'] = $this->_globalData['number_client'];
                foreach ($products as $key => $product) {
                    //Qty in line of product
                    $response['result'][0]['fis'][$key]['qtt'] = $product['qty'];
                    //Price in line of product
                    $response['result'][0]['fis'][$key]['epv'] = $product['price'];

                    //Remove discounts
                    $response['result'][0]['fis'][$key]['desconto'] = 0;

                    $response['result'][0]['fis'][$key]['desc2'] = 0;
                    $response['result'][0]['fis'][$key]['desc3'] = 0;
                    $response['result'][0]['fis'][$key]['desc4'] = 0;
                    $response['result'][0]['fis'][$key]['desc5'] = 0;
                    $response['result'][0]['fis'][$key]['desc6'] = 0;

                    //Eliminate financial discount of client
                    $response['result'][0]['fis'][$key]['efinv'] = 0;
                    $response['result'][0]['fis'][$key]['fin'] = 0;
                }
                //$this->addNewLinesDoment();
                $response = $this->activateEntity($this->table[self::INVOICE], $response['result'][0]);
                if ($response) {
                    //Eliminate financial discount of client
                    $response['result'][0]['efinv'] = 0;
                    $response['result'][0]['fin'] = 0;

                    /****************************************************************************************************
                     *     Called webservice that update all data in invoice document based on discounts, client, etc    *
                     *****************************************************************************************************/
                    $response = $this->activateEntity($this->table[self::INVOICE], $response['result'][0]);

                    if ($response) {
                        /*******************************************************************
                         *                   Called webservice that save FT                 *
                         ********************************************************************/
                        $response = $this->saveEntity($this->table[self::INVOICE], $response['result'][0]);
                        if ($response) {

                            //Enable to sign Document
                            if ($response['result'][0]['draftRecord'] == 1) {
                                $this->_globalData['ftstamp'] = $response['result'][0]['ftstamp'];

                                /*******************************************************************
                                 *                 Called webservice that sign document             *
                                 ********************************************************************/
                                $response = $this->signDocument($this->table[self::INVOICE], $response['result'][0]['ftstamp']);
                                if ($response) {
                                    $this->writeToLog("<h3>" . $response['result'][0]['nmdoc'] . " nº" . $response['result'][0]['fno'] . " is signed and inserted in Drive FX</h3>");
                                    /*******************************************************************
                                     *     Called webservice that get layout of report to create PDF    *
                                     ********************************************************************/
                                    $response = $this->getReportForPrint($this->entity[self::INVOICE], $this->_globalData['typeOfInvoices']);
                                    if ($response) {

                                        //Verify if exists template configurated and select the first
                                        $i = 0;
                                        $count = count($response['result']);
                                        $this->_globalData['sendEmail'] = false;

                                        while ($i < $count) {
                                            foreach ($response['result'][$i] as $key => $value) {
                                                if ($key == 'enabled' && $value == 1) {
                                                    $this->_globalData['sendEmail'] = true;
                                                    $this->_globalData['repstamp'] = $response['result'][$i]['repstamp'];
                                                    break;
                                                }
                                            }
                                            ++$i;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            echo "Client not exist";
            die;
        }
    }
    public function createNewBo($orderRequest)
    {
        if ($this->makeLogin()) {
            $name = $orderRequest['supplier']['name'] ?? "";
            $email = $orderRequest['supplier']['email'] ?? "";
            $mobile = $orderRequest['supplier']['mobile'] ?? "";

            if ($supplierId = $this->checkSupplierExist($name, $email, $mobile)) {
                /************************************************************************
                 *           Called webservice that obtain a new instance of Bo          *
                 *************************************************************************/
                $response = $this->getNewInstance($this->table[self::BO], 2);
                if ($response) {
                    /******************************************************************************
                     *                     Add new line to the internal document                   *
                     *******************************************************************************/
                    $newLine = [
                        "actqtt2" => false,
                        "ar2mazem" => 0,
                        "armazem" => 0,
                        "articleAutoCreationCode" => 0,
                        "bistamp" => "",
                        "bostamp" => $response['result'][0]['bostamp'],
                        "codigo" => "",
                        "componente" => false,
                        "compound" => false,
                        "desc2" => 0,
                        "desc3" => 0,
                        "desc4" => 0,
                        "desc5" => 0,
                        "desc6" => 0,
                        "desconto" => 0,
                        "design" => "",
                        "edebito" => 0,
                        "elucro" => 0,
                        "emargem" => 0,
                        "epcusto" => 0,
                        "ettdeb" => 0,
                        "ettiva" => 0,
                        "familia" => "",
                        "fechabo" => false,
                        "iva" => 0,
                        "ivaincl" => false,
                        "ivarec" => 0,
                        "litem" => "",
                        "litem2" => "",
                        "lordem" => 0,
                        "lrecno" => "",
                        "nvol" => 0,
                        "obistamp" => "",
                        "oref" => "",
                        "pbuni" => 0,
                        "pluni" => 0,
                        "qtporsatisfazer" => 0,
                        "qtt" => 0,
                        "qtt2" => 0,
                        "rdata" => "1900-01-01T00:00:00.000Z",
                        "ref" => $this->_globalData['ref_product'],
                        "rescli" => false,
                        "resfor" => false,
                        "stns" => false,
                        "tabiva" => 0,
                        "tnvol" => 0,
                        "tpbrut" => 0,
                        "tpliq" => 0,
                        "treestamp" => "",
                        "ttmoeda" => 0,
                        "tvol" => 0,
                        "unidade" => "",
                        "usr1" => "",
                        "usr2" => "",
                        "vumoeda" => 0,
                        "vuni" => 0
                    ];

                    //Add line to FtVO
                    $response['result'][0]['bis'][0] = $newLine;
                    $response = $this->activateEntity($this->table[self::BO], $response['result'][0]);

                    if ($response) {
                        //Associate client to Bo
                        $response['result'][0]['no'] = $this->_globalData['number_supplier'];

                        //Price in line of product
                        $response['result'][0]['bis'][0]['epv'] = 200;

                        //Remove discounts
                        $response['result'][0]['bis'][0]['desconto'] = 0;
                        $response['result'][0]['bis'][0]['desc2'] = 0;
                        $response['result'][0]['bis'][0]['desc3'] = 0;
                        $response['result'][0]['bis'][0]['desc4'] = 0;
                        $response['result'][0]['bis'][0]['desc5'] = 0;
                        $response['result'][0]['bis'][0]['desc6'] = 0;

                        /****************************************************************************************************
                         *    Called webservice that update all data in internal document based on discounts, client, etc    *
                         *****************************************************************************************************/
                        $response = $this->activateEntity($this->table[self::BO], $response['result'][0]);

                        if ($response) {

                            //Quantity of product
                            $response['result'][0]['bis'][0]['qtt'] = 2;

                            /****************************************************************************************************
                             *     Called webservice that update all data in invoice document based on discounts, client, etc    *
                             *****************************************************************************************************/

                            $response = $this->activateEntity($this->table[self::BO], $response['result'][0]);
                            if ($response) {
                                /*******************************************************************
                                 *                   Called webservice that save Bo                 *
                                 ********************************************************************/
                                $response = $this->saveEntity($this->table[self::BO], $response['result'][0]);
                                if ($response) {
                                    $this->_globalData['bostamp'] = $response['result'][0]['bostamp'];
                                    $this->writeToLog("<h3>" . $response['result'][0]['nmdos'] . " nº" . $response['result'][0]['obrano'] . " is inserted in Drive FX</h3>");
                                    /*******************************************************************
                                     *     Called webservice that get layout of report to create PDF    *
                                     ********************************************************************/
                                    $response = $this->getReportForPrint('bo', 2);
                                    if ($response) {

                                        //Verify if exists template configurated and select the first
                                        $i = 0;
                                        $count = count($response['result']);
                                        $this->_globalData['sendEmailSupplier'] = false;
                                        while ($i < $count) {
                                            foreach ($response['result'][$i] as $key => $value) {
                                                if ($key == 'enabled' && $value == 1) {
                                                    $this->_globalData['sendEmailSupplier'] = true;
                                                    $this->_globalData['repstampSupplier'] = $response['result'][$i]['repstamp'];
                                                    break;
                                                }
                                            }
                                            ++$i;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function sendEmailToSupplier()
    {
        $sendEmail = $this->_globalData['sendEmailSupplier'];
        $repstamp = $this->_globalData['repstamp'];
        $number_user = $this->_globalData['number_supplier'];
        $typeOfDocument = $_POST['typeOfInternalDocs'];
        $stampDocument = $this->_globalData['bostamp'];
        $entityNameTable = $this->entity[self::SUPPLIER];
        $mainEntityTable = $this->entity[self::INVOICE];
        $entityTypeTable = $this->entity[self::BO];
        $subjectEmail = 'Internal Document';
        if ($this->makeLogin()) {
            if ($sendEmail == true) {
                $response = $this->queryAsEntities($entityNameTable, "no", $number_user);
                if ($response) {
                    if ($response['result'][0]['email'] != '') {
                        //Email To
                        $emailToUser = $response['result'][0]['email'];
                        //$emailToUser = "joao.batalha@gmail.com";
                        $emailFrom = 'jbatalha@phc.pt';
                        $emailConfig = $this->getEmailConfig($emailFrom, $emailToUser, $subjectEmail);
                        $response = $this->getPrint($typeOfDocument, $entityTypeTable, $stampDocument, $repstamp, $emailConfig);
                        if ($response) {
                            $this->writeToLog("<h3>Email is sent to email defined in Drive FX</h3>");
                        }
                    }
                }
            }
        }
    }

    public function getEmailConfig($emailFrom, $emailToUser, $subjectEmail)
    {
        return [
            'bcc' => '',
            'body' => '',
            'cc' => '',
            'isBodyHtml' => false,
            'sendFrom' => $emailFrom,
            'sendTo' => $emailToUser,
            'sendToMyself' => false,
            'subject' => $subjectEmail,
        ];
    }

    public function getPrint($typeOfDocument, $mainEntityTable, $stampDocument, $repstamp, $emailConfig = [])
    {
        $url = $this->customeUrl . "REST/reportws/print";
        $paramOption = [
            'docId' => $typeOfDocument,
            'emailConfig' => $emailConfig,
            'generateOnly' => false,
            'isPreview' => false,
            'outputType' => 0,
            'printerId' => '',
            'records' => [
                0 => [
                    'docId' => $typeOfDocument,
                    'entityType' => $mainEntityTable,
                    'stamp' => $stampDocument,
                ],
            ],
            'reportStamp' => $repstamp,
            'sendToType' => 0,
            'serie' => 0,
        ];
        $params = [
            'options' => json_encode($paramOption)
        ];
        return $this->driveFxRequest($url, $params);
    }

    public function sendEmail()
    {
        $sendEmail = $this->_globalData['sendEmailSupplier'];
        $repstamp = $this->_globalData['repstamp'];
        $number_user = $this->_globalData['number_supplier'];
        $stampDocument = $this->_globalData['bostamp'];
        $typeOfDocument = $_POST['typeOfInternalDocs'];
        $entityNameTable = $this->entity[self::SUPPLIER];
        $mainEntityTable = $this->entity[self::INVOICE];
        $entityTypeTable = $this->entity[self::BO];
        $subjectEmail = 'Internal Document';
        if ($this->makeLogin()) {
            if ($sendEmail == true) {
                $response = $this->queryAsEntities($entityNameTable, "no", $number_user);
                if ($response) {
                    if ($response['result'][0]['email'] != '') {
                        //Email To
                        $emailToUser = $response['result'][0]['email'];
                        //$emailToUser = "joao.batalha@gmail.com";
                        $emailFrom = 'jbatalha@phc.pt';
                        $emailConfig = $this->getEmailConfig($emailFrom, $emailToUser, $subjectEmail);
                        $response = $this->getPrint($typeOfDocument, $entityTypeTable, $stampDocument, $repstamp, $emailConfig);
                        if ($response) {
                            $this->writeToLog("<h3>Email is sent to email defined in Drive FX</h3>");
                        }
                    }
                }
            }
        }
    }

    public function downloadPdf()
    {
        $downloadEmail = $_POST['downloadToUser'];
        $number_user = $_POST['number_client'];
        $entityNameTable = $this->entity[self::CLIENT];
        $entityTypeTable = $this->entity[self::INVOICE];

        $repstamp = $_POST['repstamp'];
        $typeOfDocument = $_POST['typeOfInvoices'];
        $stampDocument = $_POST['ftstamp'];
        $mainEntityTable = $this->entity[self::INVOICE];
        if ($this->makeLogin()) {
            /*******************************************************************
             *       Called webservice that create PDF with report enabled      *
             ********************************************************************/
            $response = $this->getPrint($typeOfDocument, $mainEntityTable, $stampDocument, $repstamp);
            $filename = $response['result'][0]['phcString'] ?? "";
            if ($filename) {
                //Download PDF
                $urlDownloadPdf = 'https://developer.phcfx.com/app/cfile.aspx?fileName=' . rawurlencode($filename);
                header('Content-Type: application/pdf');
                header("Content-Transfer-Encoding: Binary");
                header("Content-disposition: attachment; filename=" . rawurlencode($filename));
                readfile($urlDownloadPdf);
            }
        }
    }

    private function addNewLinesDoment()
    {
        $newLine = [
            "actqtt2" => false,
            "amostra" => false,
            "armazem" => 0,
            "articleAutoCreationCode" => 0,
            "avencano" => 0,
            "avencastamp" => "",
            "bistamp" => "",
            "ccstamp" => "",
            "cliref" => "",
            "codigo" => "",
            "codmotiseimp" => "",
            "componente" => false,
            "compound" => false,
            "cpoc" => 0,
            "davencaft" => "1900-01-01 00:00:00Z",
            "desc2" => 0,
            "desc3" => 0,
            "desc4" => 0,
            "desc5" => 0,
            "desc6" => 0,
            "desconto" => 0,
            "design" => "SABRINA SENHORA",
            "ecusto" => 0,
            "elucro" => 0,
            "emargem" => 0,
            "epv" => 14.99,
            "etiliquido" => 14.99,
            "ettiva" => 0,
            "evalcomissao" => 0,
            "facturaFt" => false,
            "familia" => "",
            "fechabo" => false,
            "firefs" => [],
            "fistamp" => "",
            "ftstamp" => "d08-4a6a-a07f-b6eed883678",
            "iva" => 23,
            "ivadata" => "1900-01-01 00:00:00Z",
            "ivaincl" => true,
            "ivarec" => 0,
            "litem" => "",
            "litem2" => "",
            "lordem" => 0,
            "lrecno" => "",
            "maxQttRefund" => 0,
            "miseimpstamp" => "",
            "motiseimp" => "",
            "nvol" => 0,
            "ofistamp" => "",
            "ofnstamp" => "",
            "oftstamp" => "",
            "oref" => "",
            "originalMaxQttRefund" => 0,
            "pbuni" => 0,
            "pluni" => 0,
            "pvmoeda" => 0,
            "qtt" => 1,
            "rdata" => "1900-01-01 00:00:00Z",
            "rdstamp" => "",
            "ref" => "2006035",
            "stns" => false,
            "sujirs" => false,
            "tabiva" => 0,
            "tmoeda" => 0,
            "tnvol" => 0,
            "tpbrut" => 0,
            "tpliq" => 0,
            "treestamp" => "",
            "tvol" => 0,
            "unidade" => "",
            "usr1" => "",
            "usr2" => "",
            "vuni" => 0
        ];

        //Add line to FtVO
        $response['result'][0]['fis'][0] = $newLine;

        $newLine = [
            "actqtt2" => false,
            "amostra" => false,
            "armazem" => 0,
            "articleAutoCreationCode" => 0,
            "avencano" => 0,
            "avencastamp" => "",
            "bistamp" => "",
            "ccstamp" => "",
            "cliref" => "",
            "codigo" => "",
            "codmotiseimp" => "",
            "componente" => false,
            "compound" => false,
            "cpoc" => 0,
            "davencaft" => "1900-01-01 00:00:00Z",
            "desc2" => 0,
            "desc3" => 0,
            "desc4" => 0,
            "desc5" => 0,
            "desc6" => 0,
            "desconto" => 0,
            "design" => "SABRINA SENHORA",
            "ecusto" => 0,
            "elucro" => 0,
            "emargem" => 0,
            "epv" => 17.99,
            "etiliquido" => 17.99,
            "ettiva" => 0,
            "evalcomissao" => 0,
            "facturaFt" => false,
            "familia" => "",
            "fechabo" => false,
            "firefs" => [
            ],
            "fistamp" => "",
            "ftstamp" => "d08-4a6a-a07f-b6eed883678",
            "iva" => 23,
            "ivadata" => "1900-01-01 00:00:00Z",
            "ivaincl" => true,
            "ivarec" => 0,
            "litem" => "",
            "litem2" => "",
            "lordem" => 0,
            "lrecno" => "",
            "maxQttRefund" => 0,
            "miseimpstamp" => "",
            "motiseimp" => "",
            "nvol" => 0,
            "ofistamp" => "",
            "ofnstamp" => "",
            "oftstamp" => "",
            "oref" => "",
            "originalMaxQttRefund" => 0,
            "pbuni" => 0,
            "pluni" => 0,
            "pvmoeda" => 0,
            "qtt" => 1,
            "rdata" => "1900-01-01 00:00:00Z",
            "rdstamp" => "",
            "ref" => "2006039",
            "stns" => false,
            "sujirs" => false,
            "tabiva" => 0,
            "tmoeda" => 0,
            "tnvol" => 0,
            "tpbrut" => 0,
            "tpliq" => 0,
            "treestamp" => "",
            "tvol" => 0,
            "unidade" => "",
            "usr1" => "",
            "usr2" => "",
            "vuni" => 0
        ];

        //Add line to FtVO
        $response['result'][0]['fis'][1] = $newLine;
        $newLine = [
            "actqtt2" => false,
            "amostra" => false,
            "armazem" => 0,
            "articleAutoCreationCode" => 0,
            "avencano" => 0,
            "avencastamp" => "",
            "bistamp" => "",
            "ccstamp" => "",
            "cliref" => "",
            "codigo" => "",
            "codmotiseimp" => "",
            "componente" => false,
            "compound" => false,
            "cpoc" => 0,
            "davencaft" => "1900-01-01 00:00:00Z",
            "desc2" => 0,
            "desc3" => 0,
            "desc4" => 0,
            "desc5" => 0,
            "desc6" => 0,
            "desconto" => 0,
            "design" => "GALOCHA SENHORA",
            "ecusto" => 0,
            "elucro" => 0,
            "emargem" => 0,
            "epv" => 12,
            "etiliquido" => 12,
            "ettiva" => 0,
            "evalcomissao" => 0,
            "facturaFt" => false,
            "familia" => "",
            "fechabo" => false,
            "firefs" => [
            ],
            "fistamp" => "",
            "ftstamp" => "d08-4a6a-a07f-b6eed883678",
            "iva" => 23,
            "ivadata" => "1900-01-01 00:00:00Z",
            "ivaincl" => true,
            "ivarec" => 0,
            "litem" => "",
            "litem2" => "",
            "lordem" => 0,
            "lrecno" => "",
            "maxQttRefund" => 0,
            "miseimpstamp" => "",
            "motiseimp" => "",
            "nvol" => 0,
            "ofistamp" => "",
            "ofnstamp" => "",
            "oftstamp" => "",
            "oref" => "",
            "originalMaxQttRefund" => 0,
            "pbuni" => 0,
            "pluni" => 0,
            "pvmoeda" => 0,
            "qtt" => 1,
            "rdata" => "1900-01-01 00:00:00Z",
            "rdstamp" => "",
            "ref" => "44519447",
            "stns" => false,
            "sujirs" => false,
            "tabiva" => 0,
            "tmoeda" => 0,
            "tnvol" => 0,
            "tpbrut" => 0,
            "tpliq" => 0,
            "treestamp" => "",
            "tvol" => 0,
            "unidade" => "",
            "usr1" => "",
            "usr2" => "",
            "vuni" => 0
        ];

        //Add line to FtVO
        $response['result'][0]['fis'][2] = $newLine;
    }
}
