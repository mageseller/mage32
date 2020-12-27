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
use Mageseller\DriveFx\Helper\ApiV3Helper;

class Index extends Action implements HttpGetActionInterface
{
    /**
     * @var ApiV3Helper
     */
    private $apiv3Helper;

    public function __construct(Context $context, ApiV3Helper $apiv3Helper)
    {
        parent::__construct($context);
        $this->apiv3Helper = $apiv3Helper;
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {


        $response = $this->apiv3Helper->generateAccessToken();

        $response = $this->apiv3Helper->fetchEntity('sl');
        echo "<pre>";
        print_r($response);
        die;
        // TODO: Implement execute method.
    }
}
