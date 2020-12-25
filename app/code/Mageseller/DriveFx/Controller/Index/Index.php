<?php

namespace Mageseller\DriveFx\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Mageseller\DriveFx\Helper\ApiHelper;

class Index extends Action implements HttpGetActionInterface
{
    /**
     * @var ApiHelper
     */
    private $apiHelper;

    public function __construct(Context $context, ApiHelper $apiHelper)
    {
        parent::__construct($context);
        $this->apiHelper = $apiHelper;
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $makeLogin = $this->apiHelper->makeLogin();
        //$this->helper->makeLogout();
        $html = $this->apiHelper->obtainInvoices();
        echo "<pre>";
        print_r($html);
        die;
        // $this->helper->createNewBo();

        // TODO: Implement execute method.
    }
}
