<?php
/**
 * A Magento 2 module named Mageseller/SupplierImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/SupplierImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\SupplierImport\Cron;

class XitCategoryImport
{

    protected $logger;
    /**
     * @var \Mageseller\SupplierImport\Helper\Xit
     */
    private $xitHelper;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(\Psr\Log\LoggerInterface $logger,\Mageseller\SupplierImport\Helper\Xit $xitHelper,)
    {
        $this->xitHelper = $xitHelper;
        $this->logger = $logger;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $this->xitHelper->importXitCategory();
        $this->logger->addInfo("All Xit Category successfully imported from cron");
    }
}
