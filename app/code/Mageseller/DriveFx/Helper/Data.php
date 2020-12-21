<?php

namespace Mageseller\DriveFx\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Mageseller\DriveFx\Logger\DrivefxLogger;

/**
 * Class Data
 */
class Data extends AbstractHelper
{
    const DRIVEFX_GENERAL_ENABLE = 'drivefx/general/enable';
    const DRIVEFX_GENERAL_URL = 'drivefx/general/url';
    const DRIVEFX_GENERAL_USERNAME = 'drivefx/general/username';
    const DRIVEFX_GENERAL_PASSWORD = 'drivefx/general/password';
    const DRIVEFX_GENERAL_APP_TYPE = 'drivefx/general/app_type';
    const DRIVEFX_GENERAL_COMPANY = 'drivefx/general/company';
    /**
     * @var \Mageseller\DriveFx\Logger\DrivefxLogger
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

    /**
     * Data constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context,
        DrivefxLogger $drivefxlogger
    ) {
        parent::__construct($context);
        $this->drivefxlogger = $drivefxlogger;
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

    public function makeLogout()
    {
        /*******************************************************************
         *          Called webservice that makes logout of Drive FX         *
         ********************************************************************/
        $url = $this->urlBase . "REST/UserLoginWS/userLogout";
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, false);
        $response = curl_exec($this->ch);
    }

    public function obtainInvoices()
    {
        if(!$this->typeOfInvoices){
            /*******************************************************************
             * Called webservice that obtain all invoice documents (FT, FS, FR) *
             ********************************************************************/
            $url = $this->urlBase . "REST/SearchWS/QueryAsEntities";

            // Create map with request parameters
            $params = array('itemQuery' => '{"groupByItems":[],
												"lazyLoaded":false,
												"joinEntities":[],
												"orderByItems":[],
												"SelectItems":[],
												"entityName":"Td",
												"filterItems":[{
																"comparison":0,
																"filterItem":"inactivo",
																"valueItem":"0",
																"groupItem":1,
																"checkNull":false,
																"skipCheckType":false,
																"type":"Number"
															}]}'
            );

            $response = $this->driveFxRequest($url, $params, $this->ch);
            $this->typeOfInvoices = [];
            if (curl_error($this->ch)) {
            } elseif (empty($response)) {
            } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
            } else {

                //Create a selectbox that contains all invoice documents (FT, FS or FR)
                foreach ($response['result'] as $key => $value) {
                    if ($value['tiposaft'] == 'FT' || $value['tiposaft'] == 'FS' || $value['tiposaft'] == 'FR') {
                        $this->typeOfInvoices[] = [
                            "ndoc" => $value['ndoc'],
                            "nmdoc" => $value['nmdoc'],
                        ];
                        //$_SESSION['typeOfInvoice'] .= "<option value=" . $value['ndoc'] . ">" . $value['nmdoc'] . "</option><br>";
                    }
                }
            }
        }


        echo "<form method='post' action=''>";
        echo "Choose type of invoice document that you want generate:<br><br><select id='typeOfInvoices' name='typeOfInvoices'>";
        echo "<option value='0'>Select one...</option>";
        foreach ($this->typeOfInvoices as $typeOfInvoice) {
            echo "<option value=" . $typeOfInvoice['ndoc'] . ">" . $typeOfInvoice['nmdoc'] . "</option><br>";
        }
        echo "</select>";
        echo "<br><br><br><input type='submit' name='generate_ft' value='Generate'>";
        echo "</form>";
    }

    public function driveFxRequest($url, $params, $ch)
    {
        // Build Http query using params
        $query = http_build_query($params);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $response = curl_exec($ch);

        // send response as JSON
        return $response = json_decode($response, true);
    }

    public function generateInvoice($typeOfInvoices, $email = "cliente@phc.pt")
    {
        if ($this->checkClientExist($email)) {
            if ($this->checkSupplierExist($email)) {
                if ($this->checkProductExist($email)) {

                    /************************************************************************
                     *           Called webservice that obtain a new instance of FT          *
                     *************************************************************************/
                    $url = $this->urlBase . "REST/FtWS/getNewInstance";

                    // Create map with request parameters
                    $params = array('ndos' => $_SESSION['typeOfInvoices']);
                    $response = $this->driveFxRequest($url, $params, $this->ch);

                    if (curl_error($this->ch)) {
                    } elseif (empty($response)) {
                    } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                        echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
                    } else {


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


                        $urlFt = $this->urlBase . "REST/FtWS/actEntity";


                        // Create map with request parameters
                        $paramsFt = array('entity' => json_encode($response['result'][0]),
                            'code' => 0,
                            'newValue' => json_encode([]));

                        $response = $this->driveFxRequest($urlFt, $paramsFt, $this->ch);

                        if (curl_error($this->ch)) {
                        } elseif (empty($response)) {
                        } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                            echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
                        } else {

                            //Associate client to FT
                            $response['result'][0]['no'] = $_SESSION['number_client'];
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

                            $urlFt = $this->urlBase . "REST/FtWS/actEntity";

                            // Create map with request parameters
                            $paramsFt = array('entity' => json_encode($response['result'][0]),
                                'code' => 0,
                                'newValue' => json_encode([]));
                            $response = $this->driveFxRequest($urlFt, $paramsFt, $this->ch);

                            if (curl_error($this->ch)) {
                            } elseif (empty($response)) {
                            } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                                echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
                            } else {

                                //Quantity of product
                                //$response['result'][0]['fis'][0]['qtt'] = 2;

                                /****************************************************************************************************
                                 *     Called webservice that update all data in invoice document based on discounts, client, etc    *
                                 *****************************************************************************************************/

                                $urlFt = $this->urlBase . "REST/FtWS/actEntity";

                                // Create map with request parameters
                                $paramsFt = array('entity' => json_encode($response['result'][0]),
                                    'code' => 0,
                                    'newValue' => json_encode([]));

                                $response = $this->driveFxRequest($urlFt, $paramsFt, $this->ch);

                                if (curl_error($this->ch)) {
                                } elseif (empty($response)) {
                                } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                                    echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
                                } else {

                                    /*******************************************************************
                                     *                   Called webservice that save FT                 *
                                     ********************************************************************/
                                    $url = $this->urlBase . "REST/FtWS/Save";

                                    // Create map with request parameters
                                    $params = array('itemVO' => json_encode($response['result'][0]),
                                        'runWarningRules' => 'false'
                                    );

                                    $response = $this->driveFxRequest($urlFt, $paramsFt, $this->ch);

                                    if (curl_error($this->ch)) {
                                    } elseif (empty($response)) {
                                    } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                                        echo "<h3>Error in creation of FT in Drive FX</h3>";
                                    } else {

                                        //Enable to sign Document
                                        if ($response['result'][0]['draftRecord'] == 1) {
                                            $_SESSION['ftstamp'] = $response['result'][0]['ftstamp'];

                                            /*******************************************************************
                                             *                 Called webservice that sign document             *
                                             ********************************************************************/
                                            $url = $this->urlBase . "REST/FtWS/signDocument";

                                            // Create map with request parameters
                                            $params = array('ftstamp' => $response['result'][0]['ftstamp']);

                                            $response = $this->driveFxRequest($urlFt, $paramsFt, $this->ch);

                                            if (curl_error($this->ch)) {
                                            } elseif (empty($response)) {
                                            } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                                                echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
                                            } else {
                                                echo "<h3>" . $response['result'][0]['nmdoc'] . " nº" . $response['result'][0]['fno'] . " is signed and inserted in Drive FX</h3>";

                                                /*******************************************************************
                                                 *     Called webservice that get layout of report to create PDF    *
                                                 ********************************************************************/
                                                $url = $this->urlBase . "REST/reportws/getReportsForPrint";

                                                // Create map with request parameters
                                                $params = array('entityname' => 'ft',
                                                    'numdoc' => $_SESSION['typeOfInvoices']);

                                                $response = $this->driveFxRequest($urlFt, $paramsFt, $this->ch);

                                                if (curl_error($this->ch)) {
                                                } elseif (empty($response)) {
                                                } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                                                    echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
                                                } else {
                                                    //Verify if exists template configurated and select the first
                                                    $i = 0;
                                                    $count = count($response['result']);
                                                    $_SESSION['sendEmail'] = false;

                                                    while ($i < $count) {
                                                        foreach ($response['result'][$i] as $key => $value) {
                                                            if ($key == 'enabled' && $value == 1) {
                                                                $_SESSION['sendEmail'] = true;
                                                                $_SESSION['repstamp'] = $response['result'][$i]['repstamp'];
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

    public function checkClientExist($email = "cliente@phc.pt")
    {
        if ($this->makeLogin()) {
            /************************************************************************
             *        Called webservice that find if client already exists           *
             *************************************************************************/
            $url = $this->urlBase . "REST/SearchWS/QueryAsEntities";

            // Create map with request parameters
            $params = array('itemQuery' => '{"groupByItems":[],
												"lazyLoaded":false,
												"joinEntities":[],
												"orderByItems":[],
												"SelectItems":[],
												"entityName":"Cl",
												"filterItems":[{
																"comparison":0,
																"filterItem":"email",
																"valueItem":"' + $email + '",
																"groupItem":1,
																"checkNull":false,
																"skipCheckType":false,
																"type":"Number"
															  }]}'
            );

            $response = $this->driveFxRequest($url, $params, $this->ch);

            if (curl_error($this->ch) || empty($response)) {
                return false;
            } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                return $this->createNewClient($email);
                echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
            } else {
                return true;
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
            $response = curl_exec($this->ch);
            // send response as JSON
            $response = json_decode($response, true);
            if (curl_error($this->ch)) {
                $this->isLogin =  false;
            } elseif (empty($response)) {
                $this->isLogin = false;
            } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                $this->isLogin = "Error in login. Please verify your username, password, applicationType and company.";
            } else {
                $this->isLogin = true;
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

        $url = $this->urlBase . "REST/UserLoginWS/userLoginCompany";
        // Create map with request parameters
        $params = [
            'userCode' => $this->username,
            'password' => $this->password,
            'applicationType' => $this->appType,
            'company' => $this->company
        ];

        // Build Http query using params
        $query = http_build_query($params);

        //initial request with login data
        $this->ch = curl_init();

        //URL to save cookie "ASP.NET_SessionId"
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/32.0.1700.107 Chrome/32.0.1700.107 Safari/537.36');
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);

        //Parameters passed to POST
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $query);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, '');
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, '');
        return $this->ch;
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

    public function createNewClient($name, string $email, $mobile)
    {
        if ($this->makeLogin()) {
            /************************************************************************
             *        Called webservice that obtain a new instance of client         *
             *************************************************************************/
            $url = $this->urlBase . "REST/ClWS/getNewInstance";

            // Create map with request parameters
            $params = array('ndos' => 0);

            $response = $this->driveFxRequest($url, $params, $this->ch);

            if (curl_error($this->ch)) {
            } elseif (empty($response)) {
            } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
            } else {
                //Change name and email of client
                $response['result'][0]['nome'] = $name;
                $response['result'][0]['email'] = $email;
                $response['result'][0]['ncont'] = $mobile;

                /************************************************************************
                 *                    Called webservice that save client                 *
                 *************************************************************************/
                $url = $this->urlBase . "REST/ClWS/Save";

                // Create map with request parameters
                $params = array('itemVO' => json_encode($response['result'][0]),
                    'runWarningRules' => 'false'
                );

                $response = $this->driveFxRequest($url, $params, $this->ch);

                if (curl_error($this->ch)) {
                    $_SESSION['number_client'] = 0;
                } elseif (empty($response)) {
                    $_SESSION['number_client'] = 0;
                } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                    echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
                    $_SESSION['number_client'] = 0;
                } else {
                    echo "<h3>Client nº" . $response['result'][0]['no'] . " is inserted in Drive FX</h3>";

                    return $_SESSION['number_client'] = $response['result'][0]['no'];
                }
            }
        }
    }

    private function checkSupplierExist(string $email)
    {
        if ($this->makeLogin()) {
            /************************************************************************
             *        Called webservice that find if supplier already exists           *
             *************************************************************************/
            $url = $this->urlBase . "REST/SearchWS/QueryAsEntities";

            // Create map with request parameters
            $params = array('itemQuery' => '{"groupByItems":[],
												"lazyLoaded":false,
												"joinEntities":[],
												"orderByItems":[],
												"SelectItems":[],
												"entityName":"Fl",
												"filterItems":[{
																"comparison":0,
																"filterItem":"email",
																"valueItem":"fornecedor@phc.pt",
																"groupItem":1,
																"checkNull":false,
																"skipCheckType":false,
																"type":"Number"
															  }]}'
            );

            $response = $this->driveFxRequest($url, $params, $this->ch);

            if (curl_error($this->ch)) {
            } elseif (empty($response)) {
            } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
            } else {
                //Verify if supplier exists
                if (is_array($response['result']) && !empty($response['result'][0])) {
                    $_SESSION['number_supplier'] = $response['result'][0]['no'];
                    echo "<h3>Supplier nº" . $response['result'][0]['no'] . " already exists in Drive FX</h3>";
                } else {
                    /************************************************************************
                     *        Called webservice that obtain a new instance of client         *
                     *************************************************************************/
                    $url = $this->urlBase . "REST/FlWS/getNewInstance";

                    // Create map with request parameters
                    $params = array('ndos' => 0);
                    $response = $this->driveFxRequest($url, $params, $this->ch);

                    if (curl_error($this->ch)) {
                    } elseif (empty($response)) {
                    } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                        echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
                    } else {
                        //Change name and email of supplier
                        $response['result'][0]['nome'] = 'Fornecedor de Teste - Exemplo';
                        $response['result'][0]['email'] = 'fornecedor@phc.pt';
                        $response['result'][0]['ncont'] = '987654321';

                        /************************************************************************
                         *                    Called webservice that save supplier                 *
                         *************************************************************************/
                        $url = $this->urlBase . "REST/FlWS/Save";

                        // Create map with request parameters
                        $params = array('itemVO' => json_encode($response['result'][0]),
                            'runWarningRules' => 'false'
                        );

                        $response = $this->driveFxRequest($url, $params, $this->ch);

                        if (curl_error($this->ch)) {
                            $_SESSION['number_supplier'] = 0;
                        } elseif (empty($response)) {
                            $_SESSION['number_supplier'] = 0;
                        } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                            echo "Error: " . $response['messages'][0]['messageCodeLocale'];
                            $_SESSION['number_supplier'] = 0;
                        } else {
                            echo "<h3>Supplier nº" . $response['result'][0]['no'] . " is inserted in Drive FX</h3>";

                            $_SESSION['number_supplier'] = $response['result'][0]['no'];
                        }
                    }
                }
            }
            $this->createNewSupplier();
        }
    }

    private function checkProductExist(string $email)
    {
        if ($this->makeLogin()) {
            /***********************************************************************
             *        Called webservice that find  if product already exists        *
             ************************************************************************/
            $url = $urlBase . "REST/SearchWS/QueryAsEntities";

            // Create map with request parameters
            $params = array('itemQuery' => '{"groupByItems":[],
												"lazyLoaded":false,
												"joinEntities":[],
												"orderByItems":[],
												"SelectItems":[],
												"entityName":"St",
												"filterItems":[{
																"comparison":0,
																"filterItem":"ref",
																"valueItem":"2006035",
																"groupItem":1,
																"checkNull":false,
																"skipCheckType":false,
																"type":"Number"
															}]}'
            );

            $response = $this->driveFxRequest($url, $params, $this->ch);

            if (curl_error($this->ch)) {
            } elseif (empty($response)) {
            } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
            } else {
                //Verify if product exists
                if (is_array($response['result']) && !empty($response['result'][0])) {
                    $_SESSION['ref_product'] = $response['result'][0]['ref'];
                    echo "<h3>Ref: " . $response['result'][0]['ref'] . " already exists in Drive FX</h3>";
                } else {
                    /************************************************************************
                     *        Called webservice that obtain a new instance of product        *
                     *************************************************************************/
                    $url = $urlBase . "REST/StWS/getNewInstance";

                    // Create map with request parameters
                    $params = array('ndos' => 0);
                    $response = $this->driveFxRequest($url, $params, $this->ch);


                    if (curl_error($this->ch)) {
                    } elseif (empty($response)) {
                    } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                        echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
                    } else {
                        $response['result'][0]['ref'] = '2006035'; //reference of product
                        $response['result'][0]['design'] = 'SABRINA SENHORA'; //name of product

                        $response['result'][0]['epv1'] = '14.99';    //retail price 1
                        $response['result'][0]['iva1incl'] = true;  //tax rate included
                        $response['result'][0]['inactivo'] = false; //active

                        /************************************************************************
                         *                   Called webservice that save product                 *
                         *************************************************************************/
                        $url = $this->urlBase . "REST/StWS/Save";

                        // Create map with request parameters
                        $params = array('itemVO' => json_encode($response['result'][0]),
                            'runWarningRules' => 'false'
                        );

                        $response = $this->driveFxRequest($url, $params, $this->ch);

                        if (curl_error($this->ch)) {
                            $_SESSION['ref_product'] = '';
                        } elseif (empty($response)) {
                            $_SESSION['ref_product'] = '';
                        } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                            echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
                            $_SESSION['ref_product'] = '';
                        } else {
                            echo "<h3>Ref: " . $response['result'][0]['ref'] . " is inserted in Drive FX</h3>";

                            $_SESSION['ref_product'] = $response['result'][0]['ref'];
                        }
                    }
                }
            }
            $this->createNewProduct();
        }
    }

    public function createNewBo()
    {
        if ($this->makeLogin()) {
            $ch = $this->ch;
            /************************************************************************
             *           Called webservice that obtain a new instance of Bo          *
             *************************************************************************/
            $url = $this->urlBase . "REST/BoWS/getNewInstance";

            // Create map with request parameters
            $params = array('ndos' => 2);
            $response = $this->driveFxRequest($url, $params, $this->ch);

            if (curl_error($this->ch)) {
            } elseif (empty($response)) {
            } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
            } else {

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
					"ref":"' . $_SESSION['ref_product'] . '",
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

                $urlBo = $this->urlBase . "REST/BoWS/actEntity";

                // Create map with request parameters
                $paramsBo = array('entity' => json_encode($response['result'][0]),
                    'code' => 0,
                    'newValue' => json_encode([]));

                $response = $this->driveFxRequest($urlBo, $paramsBo, $this->ch);

                if (curl_error($this->ch)) {
                } elseif (empty($response)) {
                } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                    echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
                } else {

                    //Associate client to Bo
                    $response['result'][0]['no'] = $_SESSION['number_supplier'];

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

                    $urlBo = $this->urlBase . "REST/BoWS/actEntity";

                    // Create map with request parameters
                    $paramsBo = array('entity' => json_encode($response['result'][0]),
                        'code' => 0,
                        'newValue' => json_encode([]));
                    $response = $this->driveFxRequest($urlBo, $paramsBo, $this->ch);

                    if (curl_error($this->ch)) {
                    } elseif (empty($response)) {
                    } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                        echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
                    } else {

                        //Quantity of product
                        $response['result'][0]['bis'][0]['qtt'] = 2;

                        /****************************************************************************************************
                         *     Called webservice that update all data in invoice document based on discounts, client, etc    *
                         *****************************************************************************************************/

                        $urlBo = $this->urlBase . "REST/BoWS/actEntity";

                        // Create map with request parameters
                        $paramsBo = array('entity' => json_encode($response['result'][0]),
                            'code' => 0,
                            'newValue' => json_encode([]));

                        $response = $this->driveFxRequest($urlBo, $paramsBo, $this->ch);

                        if (curl_error($ch)) {
                        } elseif (empty($response)) {
                        } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                            echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
                        } else {

                            /*******************************************************************
                             *                   Called webservice that save Bo                 *
                             ********************************************************************/
                            $url = $this->urlBase . "REST/BoWS/Save";

                            // Create map with request parameters
                            $params = array('itemVO' => json_encode($response['result'][0]),
                                'runWarningRules' => 'false'
                            );

                            $response = $this->driveFxRequest($url, $params, $this->ch);

                            if (curl_error($this->ch)) {
                            } elseif (empty($response)) {
                            } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                                echo "<h3>Error in creation of Bo in Drive FX</h3>";
                            } else {
                                $_SESSION['bostamp'] = $response['result'][0]['bostamp'];

                                echo "<h3>" . $response['result'][0]['nmdos'] . " nº" . $response['result'][0]['obrano'] . " is inserted in Drive FX</h3>";

                                /*******************************************************************
                                 *     Called webservice that get layout of report to create PDF    *
                                 ********************************************************************/
                                $url = $this->urlBase . "REST/reportws/getReportsForPrint";

                                // Create map with request parameters
                                $params = array('entityname' => 'bo',
                                    'numdoc' => 2);

                                $response = $this->driveFxRequest($url, $params, $this->ch);

                                if (curl_error($this->ch)) {
                                } elseif (empty($response)) {
                                } elseif (isset($response['messages'][0]['messageCodeLocale'])) {
                                    echo "Error: " . $response['messages'][0]['messageCodeLocale'] . "<br><br>";
                                } else {
                                    //Verify if exists template configurated and select the first
                                    $i = 0;
                                    $count = count($response['result']);
                                    $_SESSION['sendEmailSupplier'] = false;
                                    while ($i < $count) {
                                        foreach ($response['result'][$i] as $key => $value) {
                                            if ($key == 'enabled' && $value == 1) {
                                                $_SESSION['sendEmailSupplier'] = true;
                                                $_SESSION['repstampSupplier'] = $response['result'][$i]['repstamp'];
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
