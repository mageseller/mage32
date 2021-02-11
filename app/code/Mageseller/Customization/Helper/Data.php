<?php
/**
 * Mageseller
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageseller.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageseller.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageseller
 * @package   Mageseller_Customization
 * @copyright Copyright (c) 2017 Mageseller (http://www.mageseller.com/)
 * @license   https://www.mageseller.com/LICENSE.txt
 */

namespace Mageseller\Customization\Helper;

use Magento\Backend\App\Config;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

use Mageseller\Customization\Model\DevicesFactory;

/**
 * Class Data
 *
 * @package Mageseller\Osc\Helper
 */
class Data extends AbstractHelper
{
    /**
     * Image size default
     */
    const IMAGE_SIZE = '135x135';

    /**
     * General configuaration path
     */
    const GENERAL_CONFIGUARATION = 'shopbydevices/general';

    /**
     * Devices page configuration path
     */
    const BRAND_CONFIGUARATION = 'shopbydevices/devicespage';

    /**
     * Feature devices configuration path
     */
    const FEATURE_CONFIGUARATION = 'shopbydevices/devicespage/feature';

    /**
     * Search devices configuration path
     */
    const SEARCH_CONFIGUARATION = 'shopbydevices/devicespage/search';

    /**
     * Search devices configuration path
     */
    const BRAND_DETAIL_CONFIGUARATION = 'shopbydevices/devicesview';

    /**
     * Devices media path
     */
    const BRAND_MEDIA_PATH = 'mageseller/devices';

    /**
     * Default route name
     */
    const DEFAULT_ROUTE = 'devices';

    /**
     * @type \Magento\Framework\Filter\FilterManager
     */

    const XML_PATH_SHOPBYBRAND = 'shopbydevices/';

    const CATEGORY = 'category';
    const BRAND_FIRST_CHAR ='char';

    protected $_filter;

    public $translitUrl;

    /**
     * @type string
     */
    protected $_char = '';

    /**
     * @type \Mageseller\Customization\Model\DevicesFactory
     */
    protected $_devicesFactory;
    /**
     * @var Config
     */
    protected $backendConfig;
    /**
     * @type StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @type ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var array
     */
    protected $isArea = [];
    /**
     * @type
     */
    protected $_devicesCollection;

    /**
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\ObjectManagerInterface  $objectManager
     * @param \Magento\Framework\Filter\TranslitUrl      $translitUrl
     * @param \Magento\Framework\Filter\FilterManager    $filter
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ObjectManagerInterface $objectManager,
        \Magento\Framework\Filter\TranslitUrl $translitUrl,
        FilterManager $filter,
        DevicesFactory $devicesFactory
    ) {
        parent::__construct($context);
        $this->_filter         = $filter;
        $this->translitUrl     = $translitUrl;
        $this->_devicesFactory   = $devicesFactory;
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
    }

    /**
     * Is enable module on frontend
     *
     * @param  null $store
     * @return bool
     */
    public function isEnabled($store = null)
    {
        $isModuleOutputEnabled = $this->isModuleOutputEnabled();

        return $isModuleOutputEnabled && $this->getGeneralConfig('enabled', $store);
    }

    /**
     * @return \Magento\Store\Model\StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->storeManager;
    }

    /**
     * @param  $position
     * @return bool
     */
    public function canShowDevicesLink($position)
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $positionConfig = explode(',', $this->getGeneralConfig('show_position'));

        return in_array($position, $positionConfig);
    }

    /**
     * @param  null $devices
     * @return string
     */
    public function getDevicesUrl($devices = null)
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $key     = is_null($devices) ? '' : '/' . $this->processKey($devices);

        return $baseUrl . $this->getRoute() . $key . $this->getUrlSuffix();
    }

    /**
     * @param  $devices
     * @return string
     */
    public function processKey($devices)
    {
        if (!$devices) {
            return '';
        }

        $str = $devices->getUrlKey() ?: $devices->getDefaultValue();

        return $this->formatUrlKey($str);
    }

    /**
     * Format URL key from name or defined key
     *
     * @param  string $str
     * @return string
     */
    public function formatUrlKey($str)
    {
        return $this->_filter->translitUrl($str);
    }

    /**
     * @param  $devices
     * @return string
     */
    public function getDevicesImageUrl($devices)
    {
        if ($devices->getImage()) {
            $image = $devices->getImage();
        } elseif ($devices->getSwatchType() == \Magento\Swatches\Model\Swatch::SWATCH_TYPE_VISUAL_IMAGE) {
            $image = \Magento\Swatches\Helper\Media::SWATCH_MEDIA_PATH . $devices->getSwatchValue();
        } elseif ($this->getDevicesDetailConfig('default_image')) {
            $image = self::BRAND_MEDIA_PATH . '/' . $this->getDevicesDetailConfig('default_image');
        } else {
            return \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Magento\Catalog\Helper\Image')
                ->getDefaultPlaceholderUrl('small_image');
        }

        return $this->_urlBuilder->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]) . $image;
    }

    /**
     * Get Devices Title
     *
     * @return string
     */
    public function getDevicesTitle()
    {
        return $this->getGeneralConfig('link_title') ?: __('Devices');
    }

    /**
     * @param  $devices
     * @param  bool|false $short
     * @return mixed
     */
    public function getDevicesDescription($devices, $short = false)
    {
        if ($short) {
            return $devices->getShortDescription() ?: '';
        }

        return $devices->getDescription() ?: '';
    }

    /************************
     * General Configuration *************************
     *
     * @param  $code
     * @param  null $store
     * @return mixed
     */
    public function getGeneralConfig($code = '', $store = null)
    {
        $code = $code ? self::GENERAL_CONFIGUARATION . '/' . $code : self::GENERAL_CONFIGUARATION;

        return $this->getConfigValue($code, $store);
    }

    /**
     * @param  null $store
     * @return mixed
     */
    public function getAttributeCode($store = null)
    {
        //        if ($store == null)
        //        {
        //            $store = '0';
        //        }
        return $this->getGeneralConfig('attribute', $store);
    }

    /**
     * Get route name for devices.
     * If empty, default 'devices' will be used
     *
     * @param  null $store
     * @return string
     */
    public function getRoute($store = null)
    {
        $route = $this->getGeneralConfig('route', $store) ?: self::DEFAULT_ROUTE;

        return $this->formatUrlKey($route);
    }

    /**
     * Retrieve category rewrite suffix for store
     *
     * @param  int $storeId
     * @return string
     */
    public function getUrlSuffix($storeId = null)
    {
        return $this->scopeConfig->getValue(
            \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator::XML_PATH_CATEGORY_URL_SUFFIX,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /************************
     * Devices Configuration *************************
     *
     * @param  $code
     * @param  null $store
     * @return mixed
     */
    public function getDevicesConfig($code = '', $store = null)
    {
        $code = $code ? self::BRAND_CONFIGUARATION . '/' . $code : self::BRAND_CONFIGUARATION;

        return $this->getConfigValue($code, $store);
    }

    /**
     * @param  string $group
     * @param  null   $store
     * @return array
     */
    public function getImageSize($group = '', $store = null)
    {
        $imageSize = $this->getDevicesConfig($group . 'image_size') ?: self::IMAGE_SIZE;

        return explode('x', $imageSize);
    }

    /************************
     * Feature Devices Configuration *************************
     *
     * @param  $code
     * @param  null $store
     * @return mixed
     */
    public function getFeatureConfig($code = '', $store = null)
    {
        $code = $code ? self::FEATURE_CONFIGUARATION . '/' . $code : self::FEATURE_CONFIGUARATION;

        return $this->getConfigValue($code, $store);
    }

    /**
     * @param  null $store
     * @return mixed
     */
    public function enableFeature($store = null)
    {
        return $this->getSearchConfig('enable', $store);
    }

    /************************
     * Search Devices Configuration *************************
     *
     * @param  $code
     * @param  null $store
     * @return mixed
     */
    public function getSearchConfig($code = '', $store = null)
    {
        $code = $code ? self::SEARCH_CONFIGUARATION . '/' . $code : self::SEARCH_CONFIGUARATION;

        return $this->getConfigValue($code, $store);
    }

    /**
     * @param  null $store
     * @return mixed
     */
    public function enableSearch($store = null)
    {
        return $this->getSearchConfig('enable', $store);
    }

    /************************
     * Devices View Configuration *************************
     *
     * @param  $code
     * @param  null $store
     * @return mixed
     */
    public function getDevicesDetailConfig($code = '', $store = null)
    {
        $code = $code ? self::BRAND_DETAIL_CONFIGUARATION . '/' . $code : self::BRAND_DETAIL_CONFIGUARATION;

        return $this->getConfigValue($code, $store);
    }

    /**
     * @return array
     */
    public function getAllDevicesAttributeCode()
    {
        $stores         = $this->storeManager->getStores();
        $attributeCodes = [];
        array_push($attributeCodes, $this->getAttributeCode('0'));
        foreach ($stores as $store) {
            array_push($attributeCodes, $this->getAttributeCode($store->getId()));
        }
        $attributeCodes = array_unique($attributeCodes);

        return $attributeCodes;
    }

    public function getShopByDevicesConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SHOPBYBRAND . $code, $storeId);
    }

    /**
     * generate url_key for devices category
     *
     * @param  $name
     * @param  $count
     * @return string
     */
    public function generateUrlKey($name, $count)
    {
        $name = $this->strReplace($name);
        $text = $this->translitUrl->filter($name);
        if ($count == 0) {
            $count = '';
        }
        if (empty($text)) {
            return 'n-a' . $count;
        }

        return $text . $count;
    }

    /**
     * replace vietnamese characters to english characters
     *
     * @param  $str
     * @return mixed|string
     */
    public function strReplace($str)
    {
        $str = trim(mb_strtolower($str));
        $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
        $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
        $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
        $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
        $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
        $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
        $str = preg_replace('/(đ)/', 'd', $str);

        return $str;
    }

    /**
     * @param  null $cat
     * @return string
     */
    public function getCatUrl($cat = null)
    {
        $baseUrl    = $this->storeManager->getStore()->getBaseUrl();
        $devicesRoute = $this->getRoute();
        $key        = is_null($cat) ? '' : '/' . $this->processKey($cat);

        return $baseUrl . $devicesRoute . '/' . self::CATEGORY . $key . $this->getUrlSuffix();
    }

    /**
     * @param  $routePath
     * @param  $routeSize
     * @return bool
     */
    public function isDevicesRoute($routePath, $routeSize)
    {
        if ($routeSize > 3) {
            return false;
        }

        $urlSuffix  = $this->getUrlSuffix();
        $devicesRoute = $this->getRoute();
        if ($urlSuffix) {
            $devicesSuffix = strpos($devicesRoute, $urlSuffix);
            if ($devicesSuffix) {
                $devicesRoute = substr($devicesRoute, 0, $devicesSuffix);
            }
        }

        return ($routePath[0] == $devicesRoute);
    }

    //************************* Get Devices List Function ***************************
    /**
     * @param null $type
     * @param null $ids
     *
     * @param null $char
     *
     * @return mixed
     */
    public function getDevicesList($type = null, $ids = null, $char = null)
    {
        $devices = $this->_devicesFactory->create();
        switch ($type) {
            //Get Devices List by Category
        case self::CATEGORY:
            $list = $devices->getDevicesCollection(null, ['main_table.option_id' => ['in' => $ids]]);
            break;
            //Get Devices List Filtered by Devices First Char
        case self::BRAND_FIRST_CHAR:
            $list = $devices->getDevicesCollection(null, ['main_table.option_id' => ['in' => $ids]], "IF(tsv.value_id > 0, tsv.value, tdv.value) LIKE '" . $char . "%'");
            break;
        default:
            //Get Devices List
            $list = $devices->getDevicesCollection();
        }

        return $list;
    }

    //*********************** Get Category and Alpha bet Character Filter Class for Mixitup ***********************
    /**
     * Get class for mixitup filter
     *
     * @param  $devices
     * @return string
     */

    public function getFilterClass($devices)
    {
        //vietnamese unikey format
        if ($this->getShopByDevicesConfig('devicespage/devices_filter/encode_key')) {
            $firstChar = mb_substr($devices->getValue(), 0, 1, $this->getShopByDevicesConfig('devicespage/devices_filter/encode_key'));
        } else {
            $firstChar = mb_substr($devices->getValue(), 0, 1, 'UTF-8');
        }

        return is_numeric($firstChar) ? 'num' . $firstChar : $firstChar;
    }

    public function getQuickViewUrl()
    {
        $baseUrl    = $this->storeManager->getStore()->getBaseUrl();
        return $baseUrl . 'devices/index/quickview';
    }
    /**
     * @param $field
     * @param null   $scopeValue
     * @param string $scopeType
     *
     * @return array|mixed
     */
    public function getConfigValue($field, $scopeValue = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        if ($scopeValue === null && !$this->isArea()) {
            /**
 * @var Config $backendConfig
*/
            if (!$this->backendConfig) {
                $this->backendConfig = $this->objectManager->get(\Magento\Backend\App\ConfigInterface::class);
            }

            return $this->backendConfig->getValue($field);
        }

        return $this->scopeConfig->getValue($field, $scopeType, $scopeValue);
    }
    /**
     * @param string $area
     *
     * @return mixed
     */
    public function isArea($area = Area::AREA_FRONTEND)
    {
        if (!isset($this->isArea[$area])) {
            /**
 * @var State $state
*/
            $state = $this->objectManager->get(\Magento\Framework\App\State::class);

            try {
                $this->isArea[$area] = ($state->getAreaCode() == $area);
            } catch (Exception $e) {
                $this->isArea[$area] = false;
            }
        }

        return $this->isArea[$area];
    }
}
