<?php
/**
 * A Magento 2 module named Mageseller/XitImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/XitImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\XitImport\Controller\Adminhtml\Xit;

class Importcategory extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;
    protected $jsonHelper;
    /**
     * @var \Mageseller\Xit\Helper\Xit
     */
    private $xitHelper;
    /**
     * @var \Mageseller\XitImport\Logger\XitImport
     */
    protected $logger;
    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context  $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Mageseller\XitImport\Helper\Xit $xitHelper,
        \Mageseller\XitImport\Logger\XitImport $xitimportLogger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->xitHelper = $xitHelper;
        $this->logger = $xitimportLogger;
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
            $response = $this->xitHelper->importXitCategory();
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
