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
        /*$listOfRefs[] = 's001';

        echo json_encode($listOfRefs);
        echo "<br>\n";
        if (is_array($listOfRefs)) {
            foreach ($listOfRefs as $key => $value) {
                if ($key == 0) {
                    $listOfRefs = $value;
                } else {
                    $listOfRefs .= "\", \"" . $value;
                }
            }
        }
        echo '["' . $listOfRefs . '"]';die;*/
        $makeLogin = $this->apiHelper->makeLogin();
        $E1ws = $this->apiHelper->queryAsEntities('E1ws');
        echo "<pre>";
        print_r($E1ws);
        die;
        $response = $this->apiHelper->queryAsEntities('TsWS');
        echo "<pre>";
        print_r($response);die;
        $response = $this->apiHelper->getClientList();
        //$this->helper->makeLogout();
        //$html = $this->apiHelper->obtainInvoices();
        echo "<pre>";
        print_r($response);
        die;
        // $this->helper->createNewBo();

        // TODO: Implement execute method.
    }
}
