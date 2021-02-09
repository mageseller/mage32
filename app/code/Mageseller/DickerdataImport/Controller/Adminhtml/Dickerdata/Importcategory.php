<?php
/**
 * A Magento 2 module named Mageseller/DickerdataImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/DickerdataImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\DickerdataImport\Controller\Adminhtml\Dickerdata;

class Importcategory extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;
    protected $jsonHelper;
    /**
     * @var \Mageseller\Dickerdata\Helper\Dickerdata
     */
    private $dickerdataHelper;
    /**
     * @var \Mageseller\DickerdataImport\Logger\DickerdataImport
     */
    protected $logger;
    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Mageseller\DickerdataImport\Helper\Dickerdata $dickerdataHelper,
        \Mageseller\DickerdataImport\Logger\DickerdataImport $dickerdataimportLogger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->dickerdataHelper = $dickerdataHelper;
        $this->logger = $dickerdataimportLogger;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $result = [];
            $response = $this->dickerdataHelper->importDickerdataCategory();
            $result['status'] = 1;
            //$result['message'] = $response;
            $result['message'] = 'Import Successfully';
            return $this->jsonResponse($result);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
            return $this->jsonResponse($result);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
            return $this->jsonResponse($result);
        }
    }

    /**
     * Create json response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
}
