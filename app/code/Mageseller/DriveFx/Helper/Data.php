<?php

namespace Mageseller\DriveFx\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Mageseller\DriveFx\Helper\ApiHelper;
use Mageseller\DriveFx\Logger\DrivefxLogger;

/**
 * Class Data
 */
class Data extends AbstractHelper
{
    /**
     * @var \Mageseller\DriveFx\Helper\ApiHelper
     */
    private $apiHelper;
    /**
     * @var DrivefxLogger
     */
    private $drivefxlogger;


    /**
     * Data constructor.
     * @param Context $context
     * @param DrivefxLogger $drivefxlogger
     * @param ApiHelper $apiHelper
     */
    public function __construct(
        Context $context,
        DrivefxLogger $drivefxlogger,
        ApiHelper $apiHelper
    ) {
        parent::__construct($context);
        $this->drivefxlogger = $drivefxlogger;
        $this->apiHelper = $apiHelper;
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

}
