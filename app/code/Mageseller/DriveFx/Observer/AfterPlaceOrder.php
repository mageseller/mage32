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

class AfterPlaceOrder implements ObserverInterface
{
    public function __construct(Order $order)
    {
        $this->order = $order;
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
                echo "<pre>";
                print_r(json_decode(json_encode($order->getData()), true));
                die;
            }
            /*foreach ($orderids as $orderid) {
                $order = $this->order->load($orderid);
            }*/
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // Error logic
        } catch (\Exception $e) {
            // Generic error logic
        }
    }
}
