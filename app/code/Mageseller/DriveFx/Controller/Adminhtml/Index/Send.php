<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mageseller\DriveFx\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Sales\Model\OrderFactory;
use Mageseller\DriveFx\Helper\Data;

class Send extends \Magento\Backend\App\Action
{
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    public function __construct(
        Action\Context $context,
        OrderFactory $orderFactory,
        Data $helper
    ) {
        parent::__construct($context);
        $this->orderFactory = $orderFactory;
        $this->helper = $helper;
    }

    /**
     * Send DriveFx order
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('order_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        $order =  $this->orderFactory->create()->load($id);
        if ($order) {
            try {
                if ($order->getId()) {
                    $this->helper->addNewOrder($order);
                }
                //$this->orderManagement->hold($order->getEntityId());
                $this->messageManager->addSuccessMessage(__('Generated in Drive Fx.'));
            } catch (\Magento\Framework\Exception\LocalizedException | \Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
            $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
            return $resultRedirect;
        }
        $resultRedirect->setPath('sales/*/');
        return $resultRedirect;
    }
}
