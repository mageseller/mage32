<?php
namespace Mageseller\Process\Helper;

use Magento\Config\Model\ResourceModel\Config as MagentoConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config extends AbstractHelper
{
    const XML_PATH_AUTO_ASYNC_EXECUTION  = 'mageseller_process/general/auto_async_execution';
    const XML_PATH_SHOW_FILE_MAX_SIZE    = 'mageseller_process/general/show_file_max_size';
    const XML_PATH_PROCESS_TIMEOUT_DELAY = 'mageseller_process/general/timeout_delay';
    /**
     * @var MagentoConfig
     */
    protected $configuration;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context               $context
     * @param MagentoConfig         $configuration
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        MagentoConfig $configuration,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->configuration = $configuration;
        $this->storeManager = $storeManager;
    }
    /**
     * @return int
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @return int
     */
    public function getCurrentWebsiteId()
    {
        return $this->storeManager->getStore()->getWebsiteId();
    }

    /**
     * Returns a config flag
     *
     * @param  string $path
     * @param  mixed  $store
     * @return bool
     */
    public function getFlag($path, $store = null)
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Returns store locale
     *
     * @param  mixed $store
     * @return string
     */
    public function getLocale($store = null)
    {
        return $this->getValue('general/locale/code', $store);
    }

    /**
     * Get tax class id specified for shipping tax estimation
     *
     * @param  mixed $store
     * @return int
     */
    public function getShippingTaxClass($store = null)
    {
        return $this->getValue(\Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $store);
    }

    /**
     * Reads the configuration directly from the database
     *
     * @param  string $path
     * @param  string $scope
     * @param  int    $scopeId
     * @return string|false
     */
    public function getRawValue($path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        $connection = $this->configuration->getConnection();

        $select = $connection->select()
            ->from($this->configuration->getMainTable(), 'value')
            ->where('path = ?', $path)
            ->where('scope = ?', $scope)
            ->where('scope_id = ?', $scopeId);

        return $connection->fetchOne($select);
    }

    /**
     * Returns a config value
     *
     * @param  string $path
     * @param  mixed  $store
     * @return mixed
     */
    public function getValue($path, $store = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Returns store name if defined
     *
     * @param  mixed $store
     * @return string
     */
    public function getStoreName($store = null)
    {
        return $this->getValue(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_NAME, $store);
    }

    /**
     * @return bool
     */
    public function isSingleStoreMode()
    {
        return $this->storeManager->hasSingleStore();
    }

    /**
     * Set a config value
     *
     * @param string $path
     * @param string $value
     * @param string $scope
     * @param int    $scopeId
     */
    public function setValue($path, $value, $scope = 'default', $scopeId = 0)
    {
        $this->configuration->saveConfig($path, $value, $scope, $scopeId);
    }

    /**
     * Returns allowed max file size (in MB) for process files that can be viewed directly in browser
     *
     * @return int
     */
    public function getShowFileMaxSize()
    {
        return $this->getValue(self::XML_PATH_SHOW_FILE_MAX_SIZE);
    }

    /**
     * Returns delay in minutes after which a process has to be automatically cancelled (blank = no timeout).
     *
     * @return int
     */
    public function getTimeoutDelay()
    {
        return $this->getValue(self::XML_PATH_PROCESS_TIMEOUT_DELAY);
    }

    /**
     * Returns true if processes can be executed automatically
     * through an AJAX request in Magento admin, false otherwise.
     *
     * @return bool
     */
    public function isAutoAsyncExecution()
    {
        return $this->getValue(self::XML_PATH_AUTO_ASYNC_EXECUTION);
    }
}
