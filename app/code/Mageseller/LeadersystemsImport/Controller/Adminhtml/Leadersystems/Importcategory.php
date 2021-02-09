<?php
/**
 * A Magento 2 module named Mageseller/LeadersystemsImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/LeadersystemsImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\LeadersystemsImport\Controller\Adminhtml\Leadersystems;

class Importcategory extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;
    protected $jsonHelper;
    /**
     * @var \Mageseller\Leadersystems\Helper\Leadersystems
     */
    private $leadersystemsHelper;
    /**
     * @var \Mageseller\LeadersystemsImport\Logger\LeadersystemsImport
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
        \Mageseller\LeadersystemsImport\Helper\Leadersystems $leadersystemsHelper,
        \Mageseller\LeadersystemsImport\Logger\LeadersystemsImport $leadersystemsimportLogger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->leadersystemsHelper = $leadersystemsHelper;
        $this->logger = $leadersystemsimportLogger;
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
            $response = $this->leadersystemsHelper->importLeadersystemsCategory();
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
