<?php

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
