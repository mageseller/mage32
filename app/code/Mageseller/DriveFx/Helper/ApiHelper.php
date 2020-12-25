<?php

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
    const INVOICE = 'Invoice';
    const ALL_INVOICE = 'AllInvoice';
    const BO = 'Bo';
    /**
     * @var DrivefxLogger
     */
    protected $drivefxlogger;

    private $urlBase;
    private $username;
    private $password;
    private $appType;
    private $company;
    private $ch;
    private $isLogin;
    private $typeOfInvoices;
    private $_globalData;
    protected $curlClient;
    protected $table = [
        self::CLIENT => 'ClWS',
        self::SUPPLIER => 'FlWS',
        self::PRODUCT => 'StWS',
        self::INVOICE => 'FtWS',
        self::BO => 'BoWS',
    ];
    protected $entity = [
        self::CLIENT => 'Cl',
        self::SUPPLIER => 'Fl',
        self::PRODUCT => 'St',
        self::INVOICE => 'Ft',
        self::ALL_INVOICE => 'Td',
        self::BO => self::BO,
    ];

    /**
     * Data constructor.
     * @param Context $context
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

    /**
     * @return mixed
     */
    public function isEnableDebug()
    {
        return $this->getConfig(self::DRIVEFX_GENERAL_DEBUG);
    }

    /**
     * @param $info
     */
    public function writeToLog($info)
    {
        if ($this->isEnableDebug()) {
            $this->drivefxlogger->info($info);
        }
    }
    public function writeToErrorLog($info)
    {
        if ($this->isEnableDebug()) {
            $this->drivefxlogger->error($info);
        }
    }

    public function driveFxRequest($url, $params)
    {
        $this->curlClient->post($url, $params, false);
        $response = $this->curlClient->getBody();
        $response = json_decode($response, true);
        return $response;
    }
    public function initCurl()
    {
        $this->urlBase = $this->getBaseUrl();
        $this->username = $this->getUsername();
        $this->password = $this->getPassword();
        $this->appType = $this->getAppType();
        $this->company = $this->getCompany();

        $this->curlClient->setOption(CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/32.0.1700.107 Chrome/32.0.1700.107 Safari/537.36');
        $this->curlClient->setOption(CURLOPT_POST, true);
        $this->curlClient->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->curlClient->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curlClient->setOption(CURLOPT_COOKIESESSION, true);
        $this->curlClient->setOption(CURLOPT_COOKIEJAR, '');
        $this->curlClient->setOption(CURLOPT_COOKIEFILE, '');
        return $this->curlClient;
    }
    public function makeLogin()
    {
        if ($this->isLogin == null) {
            /*************************************************************
             *         Called webservice that make login in Drive FX       *
             *************************************************************/
            $this->initCurl();
            $url = $this->urlBase . "REST/UserLoginWS/userLoginCompany";
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
                $this->isLogin = true;
            } else {
                $this->isLogin = false;
            }
        }
        return $this->isLogin;
    }
    public function makeLogout()
    {
        /*******************************************************************
         *          Called webservice that makes logout of Drive FX         *
         ********************************************************************/
        $url = $this->urlBase . "REST/UserLoginWS/userLogout";
        $this->curlClient->get($url);
        $this->isLogin = false;
    }
    public function queryAsEntities($entityNameTable, $filterItem, $valueItem)
    {
        $url = $this->urlBase . "REST/SearchWS/QueryAsEntities";
        // Create map with request parameters
        $params = [
            'itemQuery' => '{"groupByItems":[],
												"lazyLoaded":false,
												"joinEntities":[],
												"orderByItems":[],
												"SelectItems":[],
												"entityName":"'.$entityNameTable.'",
												"filterItems":[{
																"comparison":0,
																"filterItem":"'.$filterItem.'",
																"valueItem":"'.$valueItem.'",
																"groupItem":1,
																"checkNull":false,
																"skipCheckType":false,
																"type":"Number"
															}]}'
        ];
        return $this->driveFxRequest($url, $params);
    }
    public function getNewInstance($entity, $ndos)
    {
        $url = $this->urlBase . "REST/$entity/getNewInstance";
        $params = ['ndos' => $ndos];
        return $this->driveFxRequest($url, $params);
    }
    public function activateEntity($entity, $paramObject, $code = 0)
    {
        $url = $this->urlBase . "REST/$entity/actEntity";
        $params = [
            'entity' => json_encode($paramObject),
            'code' => $code,
            'newValue' => json_encode([])
        ];
        return  $this->driveFxRequest($url, $params);
    }
    public function saveEntity($entity, $paramObject, $runWarningRules = 'false')
    {
        $url = $this->urlBase . "REST/$entity/Save";
        $params = [
            'itemVO' => json_encode($paramObject),
            'runWarningRules' => $runWarningRules
        ];
        return  $this->driveFxRequest($url, $params);
    }
    public function getReportForPrint($entityName, $numdoc)
    {
        $url = $this->urlBase . "REST/reportws/getReportsForPrint";
        $params = [
            'entityname' => $entityName,
            'numdoc' => $numdoc
        ];
        return $this->driveFxRequest($url, $params);
    }
    public function getEmailConfig($emailFrom, $emailToUser, $subjectEmail)
    {
        return [
            'bcc' => '',
            'body' => '',
            'cc' => '',
            'isBodyHtml' => false,
            'sendFrom' =>  $emailFrom ,
            'sendTo' =>  $emailToUser ,
            'sendToMyself' => false,
            'subject' =>  $subjectEmail ,
        ];
    }
    public function getPrint($typeOfDocument, $mainEntityTable, $stampDocument, $repstamp, $emailConfig = [])
    {
        $url = $this->urlBase . "REST/reportws/print";
        $paramOption = array(
            'docId' => $typeOfDocument,
            'emailConfig' => $emailConfig,
            'generateOnly' => false,
            'isPreview' => false,
            'outputType' => 0,
            'printerId' => '',
            'records' =>
                array(
                    0 =>
                        array(
                            'docId' => $typeOfDocument,
                            'entityType' => $mainEntityTable,
                            'stamp' => $stampDocument,
                        ),
                ),
            'reportStamp' => $repstamp,
            'sendToType' => 0,
            'serie' => 0,
        );
        $params = [
            'options' => json_encode($paramOption)
        ];
        return $this->driveFxRequest($url, $params);
    }
    public function signDocument($entity, $ftstamp)
    {
        $url = $this->urlBase . "REST/$entity/signDocument";
        $params = [
            'ftstamp' => $ftstamp
        ];
        return $this->driveFxRequest($url, $params);
    }


    public function checkClientExist($email = "cliente@phc.pt")
    {
        if ($this->makeLogin()) {
            /************************************************************************
             *        Called webservice that find if client already exists           *
             *************************************************************************/
            $response = $this->queryAsEntities($this->entity[self::CLIENT], 'email', $email);

            if (!$response) {
                $response = $this->createNewClient($email);
                if ($response) {
                    return $response['result'][0]['no'];
                }
            }
        }
        return false;
    }
    public function createNewClient($name, string $email, $mobile)
    {
        if ($this->makeLogin()) {
            /************************************************************************
             *        Called webservice that obtain a new instance of client         *
             *************************************************************************/
            $response = $this->getNewInstance($this->table[self::CLIENT], 0);

            if ($response) {
                //Change name and email of client
                $response['result'][0]['nome'] = $name;
                $response['result'][0]['email'] = $email;
                $response['result'][0]['ncont'] = $mobile;

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
                    return $response['result'][0]['no'];
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
                $response = $this->createNewProduct($sku, $name, $price);
            }
            if ($response) {
                if (is_array($response['result']) && !empty($response['result'][0])) {
                    return $response['result'][0]['ref'];
                }
            }
            return false;
        }
    }

    public function createNewProduct($sku, $name, $price, $taxInclude = true, $inactive = false)
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

    public function generateInvoice($typeOfInvoices, $email = "cliente@phc.pt")
    {
        if ($clientId = $this->checkClientExist($email)) {
            if ($supplierId = $this->checkSupplierExist($email)) {
                if ($productId = $this->checkProductExist($email)) {

                    /************************************************************************
                     *           Called webservice that obtain a new instance of FT          *
                     *************************************************************************/
                    $response = $this->getNewInstance($this->table[self::INVOICE], $typeOfInvoices);

                    if ($response) {
                        /******************************************************************************
                         *                      Add new line to the invoice document                   *
                         *******************************************************************************/


                        $newLine = '{ "actqtt2": false,
                                    "amostra": false,
                                    "armazem": 0,
                                    "articleAutoCreationCode": 0,
                                    "avencano": 0,
                                    "avencastamp": "",
                                    "bistamp": "",
                                    "ccstamp": "",
                                    "cliref": "",
                                    "codigo": "",
                                    "codmotiseimp": "",
                                    "componente": false,
                                    "compound": false,
                                    "cpoc": 0,
                                    "davencaft": "1900-01-01 00:00:00Z",
                                    "desc2": 0,
                                    "desc3": 0,
                                    "desc4": 0,
                                    "desc5": 0,
                                    "desc6": 0,
                                    "desconto": 0,
                                    "design": "SABRINA SENHORA",
                                    "ecusto": 0,
                                    "elucro": 0,
                                    "emargem": 0,
                                    "epv": 14.99,
                                    "etiliquido": 14.99,
                                    "ettiva": 0,
                                    "evalcomissao": 0,
                                    "facturaFt": false,
                                    "familia": "",
                                    "fechabo": false,
                                    "firefs": [],
                                    "fistamp": "",
                                    "ftstamp": "d08-4a6a-a07f-b6eed883678",
                                    "iva": 23,
                                    "ivadata": "1900-01-01 00:00:00Z",
                                    "ivaincl": true,
                                    "ivarec": 0,
                                    "litem": "",
                                    "litem2": "",
                                    "lordem": 0,
                                    "lrecno": "",
                                    "maxQttRefund": 0,
                                    "miseimpstamp": "",
                                    "motiseimp": "",
                                    "nvol": 0,
                                    "ofistamp": "",
                                    "ofnstamp": "",
                                    "oftstamp": "",
                                    "oref": "",
                                    "originalMaxQttRefund": 0,
                                    "pbuni": 0,
                                    "pluni": 0,
                                    "pvmoeda": 0,
                                    "qtt": 1,
                                    "rdata": "1900-01-01 00:00:00Z",
                                    "rdstamp": "",
                                    "ref": "2006035",
                                    "stns": false,
                                    "sujirs": false,
                                    "tabiva": 0,
                                    "tmoeda": 0,
                                    "tnvol": 0,
                                    "tpbrut": 0,
                                    "tpliq": 0,
                                    "treestamp": "",
                                    "tvol": 0,
                                    "unidade": "",
                                    "usr1": "",
                                    "usr2": "",
                                    "vuni": 0 }';

                        //Add line to FtVO
                        $response['result'][0]['fis'][0] = json_decode($newLine);

                        $newLine = '{ "actqtt2": false,
                                        "amostra": false,
                                        "armazem": 0,
                                        "articleAutoCreationCode": 0,
                                        "avencano": 0,
                                        "avencastamp": "",
                                        "bistamp": "",
                                        "ccstamp": "",
                                        "cliref": "",
                                        "codigo": "",
                                        "codmotiseimp": "",
                                        "componente": false,
                                        "compound": false,
                                        "cpoc": 0,
                                        "davencaft": "1900-01-01 00:00:00Z",
                                        "desc2": 0,
                                        "desc3": 0,
                                        "desc4": 0,
                                        "desc5": 0,
                                        "desc6": 0,
                                        "desconto": 0,
                                        "design": "SABRINA SENHORA",
                                        "ecusto": 0,
                                        "elucro": 0,
                                        "emargem": 0,
                                        "epv": 17.99,
                                        "etiliquido": 17.99,
                                        "ettiva": 0,
                                        "evalcomissao": 0,
                                        "facturaFt": false,
                                        "familia": "",
                                        "fechabo": false,
                                        "firefs": [],
                                        "fistamp": "",
                                        "ftstamp": "d08-4a6a-a07f-b6eed883678",
                                        "iva": 23,
                                        "ivadata": "1900-01-01 00:00:00Z",
                                        "ivaincl": true,
                                        "ivarec": 0,
                                        "litem": "",
                                        "litem2": "",
                                        "lordem": 0,
                                        "lrecno": "",
                                        "maxQttRefund": 0,
                                        "miseimpstamp": "",
                                        "motiseimp": "",
                                        "nvol": 0,
                                        "ofistamp": "",
                                        "ofnstamp": "",
                                        "oftstamp": "",
                                        "oref": "",
                                        "originalMaxQttRefund": 0,
                                        "pbuni": 0,
                                        "pluni": 0,
                                        "pvmoeda": 0,
                                        "qtt": 1,
                                        "rdata": "1900-01-01 00:00:00Z",
                                        "rdstamp": "",
                                        "ref": "2006039",
                                        "stns": false,
                                        "sujirs": false,
                                        "tabiva": 0,
                                        "tmoeda": 0,
                                        "tnvol": 0,
                                        "tpbrut": 0,
                                        "tpliq": 0,
                                        "treestamp": "",
                                        "tvol": 0,
                                        "unidade": "",
                                        "usr1": "",
                                        "usr2": "",
                                        "vuni": 0 }';

                        //Add line to FtVO
                        $response['result'][0]['fis'][1] = json_decode($newLine);
                        $newLine = '{ "actqtt2": false,
                                        "amostra": false,
                                        "armazem": 0,
                                        "articleAutoCreationCode": 0,
                                        "avencano": 0,
                                        "avencastamp": "",
                                        "bistamp": "",
                                        "ccstamp": "",
                                        "cliref": "",
                                        "codigo": "",
                                        "codmotiseimp": "",
                                        "componente": false,
                                        "compound": false,
                                        "cpoc": 0,
                                        "davencaft": "1900-01-01 00:00:00Z",
                                        "desc2": 0,
                                        "desc3": 0,
                                        "desc4": 0,
                                        "desc5": 0,
                                        "desc6": 0,
                                        "desconto": 0,
                                        "design": "GALOCHA SENHORA",
                                        "ecusto": 0,
                                        "elucro": 0,
                                        "emargem": 0,
                                        "epv": 12,
                                        "etiliquido": 12,
                                        "ettiva": 0,
                                        "evalcomissao": 0,
                                        "facturaFt": false,
                                        "familia": "",
                                        "fechabo": false,
                                        "firefs": [],
                                        "fistamp": "",
                                        "ftstamp": "d08-4a6a-a07f-b6eed883678",
                                        "iva": 23,
                                        "ivadata": "1900-01-01 00:00:00Z",
                                        "ivaincl": true,
                                        "ivarec": 0,
                                        "litem": "",
                                        "litem2": "",
                                        "lordem": 0,
                                        "lrecno": "",
                                        "maxQttRefund": 0,
                                        "miseimpstamp": "",
                                        "motiseimp": "",
                                        "nvol": 0,
                                        "ofistamp": "",
                                        "ofnstamp": "",
                                        "oftstamp": "",
                                        "oref": "",
                                        "originalMaxQttRefund": 0,
                                        "pbuni": 0,
                                        "pluni": 0,
                                        "pvmoeda": 0,
                                        "qtt": 1,
                                        "rdata": "1900-01-01 00:00:00Z",
                                        "rdstamp": "",
                                        "ref": "44519447",
                                        "stns": false,
                                        "sujirs": false,
                                        "tabiva": 0,
                                        "tmoeda": 0,
                                        "tnvol": 0,
                                        "tpbrut": 0,
                                        "tpliq": 0,
                                        "treestamp": "",
                                        "tvol": 0,
                                        "unidade": "",
                                        "usr1": "",
                                        "usr2": "",
                                        "vuni": 0 }';

                        //Add line to FtVO
                        $response['result'][0]['fis'][2] = json_decode($newLine);
                        $response = $this->activateEntity($this->table[self::INVOICE], $response['result'][0]);
                        if ($response) {
                            //Associate client to FT
                            $response['result'][0]['no'] = $this->_globalData['number_client'];
                            /*
                            //Price in line of product
                            $response['result'][0]['fis'][0]['epv'] = 1;

                            //Remove discounts
                            $response['result'][0]['fis'][0]['desconto'] = 0;
                            $response['result'][0]['fis'][0]['desc2'] = 0;
                            $response['result'][0]['fis'][0]['desc3'] = 0;
                            $response['result'][0]['fis'][0]['desc4'] = 0;
                            $response['result'][0]['fis'][0]['desc5'] = 0;
                            $response['result'][0]['fis'][0]['desc6'] = 0;
                            */
                            //Eliminate financial discount of client
                            $response['result'][0]['efinv'] = 0;
                            $response['result'][0]['fin'] = 0;

                            /****************************************************************************************************
                             *     Called webservice that update all data in invoice document based on discounts, client, etc    *
                             *****************************************************************************************************/
                            $response = $this->activateEntity($this->table[self::INVOICE], $response['result'][0]);

                            if ($response) {
                                //Quantity of product
                                //$response['result'][0]['fis'][0]['qtt'] = 2;

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
                                                $response = $this->getReportForPrint('ft', $this->_globalData['typeOfInvoices']);
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
                    }
                }
            }
        } else {
            echo "Client not exist";
            die;
        }
    }

    public function createNewBo()
    {
        if ($this->makeLogin()) {
            $ch = $this->ch;
            /************************************************************************
             *           Called webservice that obtain a new instance of Bo          *
             *************************************************************************/
            $response = $this->getNewInstance($this->table[self::BO], 2);
            if ($response) {
                /******************************************************************************
                 *                     Add new line to the internal document                   *
                 *******************************************************************************/
                $newLine = '{ "actqtt2":false,
					"ar2mazem":0,
					"armazem":0,
					"articleAutoCreationCode":0,
					"bistamp":"",
					"bostamp":"' . $response['result'][0]['bostamp'] . '",
					"codigo":"",
					"componente":false,
					"compound":false,
					"desc2":0,
					"desc3":0,
					"desc4":0,
					"desc5":0,
					"desc6":0,
					"desconto":0,
					"design":"",
					"edebito":0,
					"elucro":0,
					"emargem":0,
					"epcusto":0,
					"ettdeb":0,
					"ettiva":0,
					"familia":"",
					"fechabo":false,
					"iva":0,
					"ivaincl":false,
					"ivarec":0,
					"litem":"",
					"litem2":"",
					"lordem":0,
					"lrecno":"",
					"nvol":0,
					"obistamp":"",
					"oref":"",
					"pbuni":0,
					"pluni":0,
					"qtporsatisfazer":0,
					"qtt":0,
					"qtt2":0,
					"rdata":"1900-01-01T00:00:00.000Z",
					"ref":"' . $this->_globalData['ref_product'] . '",
					"rescli":false,
					"resfor":false,
					"stns":false,
					"tabiva":0,
					"tnvol":0,
					"tpbrut":0,
					"tpliq":0,
					"treestamp":"",
					"ttmoeda":0,
					"tvol":0,
					"unidade":"",
					"usr1":"",
					"usr2":"",
					"vumoeda":0,
					"vuni":0 
				}';

                //Add line to FtVO
                $response['result'][0]['bis'][0] = json_decode($newLine);
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

    public function sendEmailToSupplier()
    {
        $sendEmail = $_POST['sendEmailSupplier'];
        $repstamp = $_POST['repstampSupplier'];
        $number_user = $_POST['number_supplier'];
        $typeOfDocument = $_POST['typeOfInternalDocs'];
        $stampDocument = $_POST['bostamp'];
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

    public function sendEmail()
    {
        $sendEmail = $_POST['sendEmailSupplier'];
        $repstamp = $_POST['repstampSupplier'];
        $number_user = $_POST['number_supplier'];
        $typeOfDocument = $_POST['typeOfInternalDocs'];
        $stampDocument = $_POST['bostamp'];
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
}
