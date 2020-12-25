<?php

namespace Mageseller\DriveFx\HTTP\Client;

use Mageseller\DriveFx\Logger\DrivefxLogger;
use phpDocumentor\Reflection\Types\Parent_;

/**
 * Class to work with HTTP protocol using curl library
 *
 * @author      Mageseller <satis29g@hotmail.com>
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Curl extends \Magento\Framework\HTTP\Client\Curl
{
    /**
     * Max supported protocol by curl CURL_SSLVERSION_TLSv1_2
     * @var int
     */
    private $sslVersion;
    /**
     * @var DrivefxLogger
     */
    protected $drivefxlogger;

    /**
     * @param DrivefxLogger $drivefxlogger
     * @param int|null $sslVersion
     */
    public function __construct(DrivefxLogger $drivefxlogger, $sslVersion = null)
    {
        parent::__construct($sslVersion);
        $this->sslVersion = $sslVersion;
        $this->drivefxlogger = $drivefxlogger;
    }
    /**
     * Make GET request
     *
     * @param string $uri uri relative to host, ex. "/index.php"
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
     * @param string $uri
     * @param array|string $params
     * @param bool $post
     * @return void
     *
     * @see \Magento\Framework\HTTP\Client#post($uri, $params)
     */
    public function post($uri, $params, $post = true)
    {
        $this->makeRequest("POST", $uri, $params, $post);
    }

    /**
     * Make request
     *
     * String type was added to parameter $param in order to support sending JSON or XML requests.
     * This feature was added base on Community Pull Request https://github.com/magento/magento2/pull/8373
     *
     * @param string $method
     * @param string $uri
     * @param array|string $params - use $params as a string in case of JSON or XML POST request.
     * @param bool $post
     * @return void
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function makeRequest($method, $uri, $params = [], $post = true)
    {
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
        $err = curl_errno($this->_ch);
        if ($err) {
            $this->doError(curl_error($this->_ch));
        }
    }

    /**
     *
     */
    public function closeCurl()
    {
        curl_close($this->_ch);
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
        if ($error_msg = curl_error($this->_ch)) {
            $url = curl_getinfo($this->_ch, CURLINFO_EFFECTIVE_URL);
            $this->drivefxlogger->addError("$url : Curl Error: ".$error_msg);
        } elseif (empty($this->_responseBody)) {
            $url = curl_getinfo($this->_ch, CURLINFO_EFFECTIVE_URL);
            $this->drivefxlogger->addError("$url : Response Empty");
        } elseif (isset($this->_responseBody['messages'][0]['messageCodeLocale'])) {
            $url = curl_getinfo($this->_ch, CURLINFO_EFFECTIVE_URL);
            $this->drivefxlogger->addError("$url :Error: " . $this->_responseBody['messages'][0]['messageCodeLocale']);
        } else {
            return $this->_responseBody;
        }
        return false;
    }
}
