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
use Mageseller\DriveFx\Logger\DrivefxLogger;
use const Magento\Sales\Model\Order\Item;

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
     * @param ApiV3Helper $apiHelper
     */
    public function __construct(
        Context $context,
        DrivefxLogger $drivefxlogger,
        ApiV3Helper $apiHelper
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
        /*$this->apiHelper->generateAccessToken();
        $response = $this->apiHelper->fetchEntity('st');
        echo "<pre>";
        print_r($response);
        die;*/
        $orderRequest = [];

        $orderRequest['supplier'] = [];
        $orderRequest['customer'] = [];
        $orderRequest['order'] = $order->getData();

        $orderRequest['customer']['customer_id'] = $order->getCustomerId();
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
        $orderRequest['customer']['taxNumber'] = $billingAddress->getVatId();

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
        foreach ($visibleItem as  $item) {
            $product = $item->getProduct();
            $orderRequest['products'][] = [
                'sku' => $item->getSku(),
                'name' => $product->getName(),
                'description' => $item->getDescription(),
                'unitPrice' => floatval($item->getPrice()),
                'price' => $product->getRowTotal(),
                'qty' => intval($item->getQtyOrdered()),
            ];
        }
        $this->apiHelper->createDocument($orderRequest);

    }
}
