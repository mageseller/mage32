<?php

namespace Mageseller\DriveFx\Plugin;

class CreateOrderInDriveFx extends \Magento\Sales\Block\Adminhtml\Order\View
{
    public function beforeSetLayout(\Magento\Sales\Block\Adminhtml\Order\View $view)
    {
        $order = $view->getOrder();
        if (!($order->getData('bodata_reposnse') && $order->getData('invoice_response'))) {
            $message ='Are you sure you want to do this?';
            $buttonUrl = $view->getUrl(
                'drivefx/index/send',
                ['order_id' =>  $view->getOrderId()]
            );

            $view->addButton(
                'drivefx_btn',
                [
                    'label' => __('Send to DriveFx'),
                    'class' => 'drivefx_btn primary',
                    'onclick' => "confirmSetLocation('{$message}', '{$buttonUrl}')"
                ]
            );
        }
    }
}
