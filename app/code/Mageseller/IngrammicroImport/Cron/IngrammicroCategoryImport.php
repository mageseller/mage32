<?php
/**
 * A Magento 2 module named Mageseller/IngrammicroImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/IngrammicroImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\IngrammicroImport\Cron;

class IngrammicroCategoryImport
{
    protected $logger;
    /**
     * @var \Mageseller\IngrammicroImport\Helper\Ingrammicro
     */
    private $ingrammicroHelper;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface                         $logger
     * @param \Mageseller\IngrammicroImport\Helper\Ingrammicro $ingrammicroHelper
     */
    public function __construct(\Psr\Log\LoggerInterface $logger, \Mageseller\IngrammicroImport\Helper\Ingrammicro $ingrammicroHelper)
    {
        $this->ingrammicroHelper = $ingrammicroHelper;
        $this->logger = $logger;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $this->ingrammicroHelper->importIngrammicroCategory();
        $this->logger->addInfo("All Ingrammicro Category successfully imported from cron");
    }
}
