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

namespace Mageseller\DickerdataImport\Cron;

class DickerdataCategoryImport
{
    protected $logger;
    /**
     * @var \Mageseller\DickerdataImport\Helper\Dickerdata
     */
    private $dickerdataHelper;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Mageseller\DickerdataImport\Helper\Dickerdata $dickerdataHelper
     */
    public function __construct(\Psr\Log\LoggerInterface $logger, \Mageseller\DickerdataImport\Helper\Dickerdata $dickerdataHelper)
    {
        $this->dickerdataHelper = $dickerdataHelper;
        $this->logger = $logger;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $this->dickerdataHelper->importDickerdataCategory();
        $this->logger->addInfo("All Dickerdata Category successfully imported from cron");
    }
}
