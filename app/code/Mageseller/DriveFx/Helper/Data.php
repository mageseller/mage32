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

use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order;
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
     * @var Magento\CatalogInventory\Model\Stock\StockItemRepository
     */
    protected $stockItemRepository;
    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * Data constructor.
     * @param Context $context
     * @param DrivefxLogger $drivefxlogger
     * @param ApiV3Helper $apiHelper
     * @param CustomerFactory $customerFactory
     * @param StockItemRepository $stockItemRepository
     */
    public function __construct(
        Context $context,
        DrivefxLogger $drivefxlogger,
        ApiV3Helper $apiHelper,
        CustomerFactory $customerFactory,
        StockItemRepository $stockItemRepository
    ) {
        parent::__construct($context);
        $this->drivefxlogger = $drivefxlogger;
        $this->apiHelper = $apiHelper;
        $this->stockItemRepository = $stockItemRepository;
        $this->customerFactory = $customerFactory;
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

    public function getStockItem($productId)
    {
        return $this->stockItemRepository->get($productId);
    }
    public function addNewOrder(Order $order)
    {
        if ($order->getData('bodata_reposnse') && $order->getData('invoice_response')) {
            return $this;
        }
        $orderRequest = [];

        $orderRequest['supplier'] = [];
        $orderRequest['customer'] = [];
        $orderRequest['order'] = $order->getData();
        $orderRequest['orderObject'] = $order;
        $customerId = $order->getCustomerId();
        if ($customerId) {
            $customer = $this->customerFactory->create()->load($customerId);
            $orderRequest['customerObject'] = $customer;
        }

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
        $orderRequest['productsObject'] = [];
        $visibleItem = $order->getAllVisibleItems();
        foreach ($visibleItem as $item) {
            $product = $item->getProduct();
            /**
             * @var \Magento\Sales\Model\Order\Item $item
            */

            $orderRequest['products'][] = [
                'sku' => $item->getSku(),
                'name' => $product->getName(),
                'description' => $item->getDescription(),
                'unitPrice' => floatval($item->getPriceInclTax()),
                'taxAmount' => floatval($item->getTaxAmount()),
                'taxPercentage' => $item->getTaxPercent(),
                'discount' => $item->getDiscountAmount(),
                'price' => $product->getRowTotal(),
                'qty' => intval($item->getQtyOrdered()),
                'stock_qty' => $this->getStockItem($product->getId())->getQty(),
                'is_virtual' => $product->isVirtual(),
                'productObjects' =>  $product
            ];
        }
        $this->apiHelper->createDocument($orderRequest);
    }
}
