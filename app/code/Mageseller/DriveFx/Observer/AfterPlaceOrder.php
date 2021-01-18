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

namespace Mageseller\DriveFx\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Mageseller\DriveFx\Helper\Data;
use Mageseller\DriveFx\Helper\ApiHelper;

class AfterPlaceOrder implements ObserverInterface
{
    /**
     * @var Order
     */
    private $order;
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * AfterPlaceOrder constructor.
     * @param Order $order
     * @param Data $helper
     * @param ApiHelper $helper
     */
    public function __construct(Order $order, Data $helper, ApiHelper $apiHelper)
    {
        $this->order = $order;
        $this->helper = $helper;
        $this->apiHelper = $apiHelper;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $orderids = $observer->getEvent()->getOrderIds();
        $order = $observer->getEvent()->getOrder();
        try {
            if ($order->getId()) {
                $this->helper->addNewOrder($order);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->apiHelper->writeToLog($e->getMessage());
            $this->apiHelper->writeToLog($e->getTraceAsString());
            // Error logic
        } catch (\Exception $e) {
            $this->apiHelper->writeToLog($e->getMessage());
            $this->apiHelper->writeToLog($e->getTraceAsString());
            // Generic error logic
        }
    }
}
