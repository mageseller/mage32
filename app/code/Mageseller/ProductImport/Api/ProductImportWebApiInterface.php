<?php

namespace Mageseller\ProductImport\Api;

use Exception;

/**
 * @author Patrick van Bergen
 */
interface ProductImportWebApiInterface
{
    /**
     * Imports products from XML
     *
     * @api
     * @return \Mageseller\ProductImport\Api\ProductImportWebApiLoggerInterface
     * @throws Exception
     */
    public function process();
}