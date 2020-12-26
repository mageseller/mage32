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
    /**
     * @var ApiHelper
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
    public function addNewOrder(\Magento\Sales\Model\Order $order)
    {
        $orderRequest = [];
        $makeLogin = $this->apiHelper->makeLogin();
        if ($makeLogin) {
            $orderRequest['supplier'] = [];
            $orderRequest['customer'] = [];

            $orderRequest['customer']['name'] = $order->getCustomerFirstname() . " " . $order->getCustomerLastname();
            $orderRequest['customer']['email'] = $order->getCustomerEmail();
            $shippingAddress = $order->getShippingAddress();
            $billingAddress = $order->getBillingAddress();
            $street = is_array($billingAddress->getStreet())
                ? implode(",", $billingAddress->getStreet())
                : $billingAddress->getStreet();

            $orderRequest['customer']['street'] = $street;
            $orderRequest['customer']['city'] = $billingAddress->getCity();
            $orderRequest['customer']['mobile'] = $billingAddress->getTelephone();
            $orderRequest['customer']['postcode'] = $billingAddress->getPostcode();
            $orderRequest['customer']['country_id'] = $billingAddress->getCountryId();
            if ($shippingAddress) {
                $shippingStreet = is_array($shippingAddress->getStreet())
                    ? implode(",", $shippingAddress->getStreet())
                    : $shippingAddress->getStreet();
                $orderRequest['customer']['shipping_street'] =  $shippingStreet;
                $orderRequest['customer']['shipping_city'] = $shippingAddress->getCity();
                $orderRequest['customer']['shipping_postcode'] = $shippingAddress->getPostcode();
                $orderRequest['customer']['shipping_country_id'] = $shippingAddress->getCountryId();
            }


            $orderRequest['products'] = [];
            $visibleItem = $order->getAllVisibleItems();
            foreach ($visibleItem as $item) {
                $product = $item->getProduct();
                $orderRequest['products'][] = [
                    'sku' => $item->getSku(),
                    'name' => $product->getName(),
                    'price' => $product->getRowTotal(),
                    'qty' => intval($item->getQtyOrdered()),
                ];
            }
        }
    }
}
