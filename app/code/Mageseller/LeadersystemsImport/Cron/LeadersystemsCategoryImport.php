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

namespace Mageseller\LeadersystemsImport\Cron;

class LeadersystemsCategoryImport
{
    protected $logger;
    /**
     * @var \Mageseller\LeadersystemsImport\Helper\Leadersystems
     */
    private $leadersystemsHelper;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface                             $logger
     * @param \Mageseller\LeadersystemsImport\Helper\Leadersystems $leadersystemsHelper
     */
    public function __construct(\Psr\Log\LoggerInterface $logger, \Mageseller\LeadersystemsImport\Helper\Leadersystems $leadersystemsHelper)
    {
        $this->leadersystemsHelper = $leadersystemsHelper;
        $this->logger = $logger;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $this->leadersystemsHelper->importLeadersystemsCategory();
        $this->logger->addInfo("All Leadersystems Category successfully imported from cron");
    }
}
