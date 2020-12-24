<?php

namespace Mageseller\DriveFx\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Mageseller\DriveFx\Helper\Data;

class Index extends Action implements HttpGetActionInterface
{
    /**
     * @var Data
     */
    private $helper;

    public function __construct(Context $context, Data $helper)
    {
        parent::__construct($context);
        $this->helper = $helper;
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $this->helper->writeToLog("ds");
        die;
        $makeLogin = $this->helper->makeLogin();
        $this->helper->obtainInvoices();
       // $this->helper->createNewBo();

        // TODO: Implement execute method.
    }
}
