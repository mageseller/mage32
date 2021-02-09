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

namespace Mageseller\DriveFx\HTTP\Client;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Mageseller\DriveFx\Logger\DrivefxLogger;

/**
 * Class to work with HTTP protocol using curl library
 *
 * @author                                           Mageseller <satis29g@hotmail.com>
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class V3Curl extends \Magento\Framework\HTTP\Client\Curl
{
    const DRIVEFX_GENERAL_DEBUG = 'drivefx/general/debug';
    /**
     * Max supported protocol by curl CURL_SSLVERSION_TLSv1_2
     *
     * @var int
     */
    private $sslVersion;
    /**
     * @var DrivefxLogger
     */
    protected $drivefxlogger;
    private $curl_errno;
    private $curl_getinfo;
    private $url;
    private $customLogger;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param DrivefxLogger $drivefxlogger
     * @param int|null      $sslVersion
     */
    public function __construct(DrivefxLogger $drivefxlogger, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, $sslVersion = null)
    {
        parent::__construct($sslVersion);
        $this->sslVersion = $sslVersion;
        $this->scopeConfig = $scopeConfig;
        $this->drivefxlogger = $drivefxlogger;
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/drivefxV3Log.log');
        $this->customLogger = new \Zend\Log\Logger();
        $this->customLogger->addWriter($writer);
    }
    public function getConfig($path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $scope
        );
    }
    public function isEnableDebug()
    {
        return $this->getConfig(self::DRIVEFX_GENERAL_DEBUG); // Pass store id in second parameter
    }
    /**
     * Make GET request
     *
     * @param  string $uri uri relative to host, ex. "/index.php"
     * @return void
     */
    public function get($uri)
    {
        $this->makeRequest("GET", $uri);
    }

    /**
     * Make POST request
     *
     * String type was added to parameter $param in order to support sending JSON or XML requests.
     * This feature was added base on Community Pull Request https://github.com/magento/magento2/pull/8373
     *
     * @param  string       $uri
     * @param  array|string $params
     * @param  bool         $post
     * @return void
     *
     * @see \Magento\Framework\HTTP\Client#post($uri, $params)
     */
    public function post($uri, $params, $post = true)
    {
        $this->makeRequest("POST", $uri, $params, $post);
    }
    public function writeToLog($info, $comment = "")
    {
        if ($this->isEnableDebug()) {
            $info = is_array($info) || is_object($info) ? json_decode(json_encode($info), true) : $info;
            if ($comment) {
                $info = "$comment : " . print_r($info, true);
            }
            $this->customLogger->notice($info);
        }
    }
    /**
     * Make request
     *
     * String type was added to parameter $param in order to support sending JSON or XML requests.
     * This feature was added base on Community Pull Request https://github.com/magento/magento2/pull/8373
     *
     * @param                                        string       $method
     * @param                                        string       $uri
     * @param                                        array|string $params - use $params as a string in case of JSON or XML POST request.
     * @param                                        bool         $post
     * @return                                       void
     * @throws                                       \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function makeRequest($method, $uri, $params = [], $post = true)
    {
        $this->writeToLog("----------------------------------------------------");
        $this->writeToLog("URL : $uri");
        $this->writeToLog("METHOD : $method");
        $this->writeToLog("REQUEST : " . print_r($params, true));
        if ($this->_ch == null) {
            $this->_ch = curl_init();
        }

        $this->curlOption(CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS | CURLPROTO_FTP | CURLPROTO_FTPS);
        $this->curlOption(CURLOPT_URL, $uri);
        if ($method == 'POST') {
            $this->curlOption(CURLOPT_POST, $post);
            $this->curlOption(CURLOPT_POSTFIELDS, is_array($params) ? http_build_query($params) : $params);
        } elseif ($method == "GET") {
            $this->curlOption(CURLOPT_HTTPGET, 1);
        } else {
            $this->curlOption(CURLOPT_CUSTOMREQUEST, $method);
        }

        if (count($this->_headers)) {
            $heads = [];
            foreach ($this->_headers as $k => $v) {
                $heads[] = $k . ': ' . $v;
            }
            $this->curlOption(CURLOPT_HTTPHEADER, $heads);
        }

        if (count($this->_cookies)) {
            $cookies = [];
            foreach ($this->_cookies as $k => $v) {
                $cookies[] = "{$k}={$v}";
            }
            $this->curlOption(CURLOPT_COOKIE, implode(";", $cookies));
        }

        if ($this->_timeout) {
            $this->curlOption(CURLOPT_TIMEOUT, $this->_timeout);
        }

        if ($this->_port != 80) {
            $this->curlOption(CURLOPT_PORT, $this->_port);
        }

        $this->curlOption(CURLOPT_RETURNTRANSFER, 1);
        $this->curlOption(CURLOPT_HEADERFUNCTION, [$this, 'parseHeaders']);
        if ($this->sslVersion !== null) {
            $this->curlOption(CURLOPT_SSLVERSION, $this->sslVersion);
        }

        if (count($this->_curlUserOptions)) {
            foreach ($this->_curlUserOptions as $k => $v) {
                $this->curlOption($k, $v);
            }
        }

        $this->_headerCount = 0;
        $this->_responseHeaders = [];
        $this->_responseBody = curl_exec($this->_ch);
        $this->curl_errno = curl_errno($this->_ch);
        $this->curl_getinfo = curl_getinfo($this->_ch);
        $this->url = $this->curl_getinfo['url'] ?? "";
        $httpCode = $this->curl_getinfo['http_code'] ?? "";
        if ($this->curl_errno) {
            $this->drivefxlogger->addError("$this->url : Curl Error: " . $this->curl_errno);
            $this->doError(curl_error($this->_ch));
            $this->writeToLog("RESPONSE ERROR: " . $this->curl_errno);
        }
        $this->writeToLog("HTTP STATUS CODE : " . $httpCode);
        $this->writeToLog("RESPONSE : " . print_r($this->_responseBody, true));
        $this->writeToLog("----------------------------------------------------");
        $this->closeCurl();
    }

    /**
     *
     */
    public function closeCurl()
    {
        curl_close($this->_ch);
        $this->_ch = null;
    }

    /**
     *
     */
    public function resetCurl()
    {
        $this->_ch = curl_init();
    }

    /**
     * @return false|string
     */
    public function getBody()
    {
        if ($this->curl_errno) {
        } elseif (empty($this->_responseBody)) {
            $url = curl_getinfo($this->_ch, CURLINFO_EFFECTIVE_URL);
            $this->drivefxlogger->addError("{$this->url} : Response Empty");
        } elseif (isset($this->_responseBody['messages'][0]['messageCodeLocale'])) {
            $url = curl_getinfo($this->_ch, CURLINFO_EFFECTIVE_URL);
            $this->drivefxlogger->addError("{$this->url} :Error: " . $this->_responseBody['messages'][0]['messageCodeLocale']);
        } else {
            return $this->_responseBody;
        }
        return false;
    }
}
