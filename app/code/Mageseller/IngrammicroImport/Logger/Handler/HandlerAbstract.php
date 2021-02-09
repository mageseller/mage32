<?php
/**
 * HandlerAbstract
 *
 * @copyright Copyright © 2020 Mageseller. All rights reserved.
 * @author    satish29g@hotmail.com
 */

namespace Mageseller\IngrammicroImport\Logger\Handler;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;

class HandlerAbstract extends Base
{
    /**
     * HandlerAbstract constructor.
     *
     * Set default filePath for IngrammicroImport logs folder
     *
     * @param DriverInterface $filesystem
     * @param null|string     $filePath
     */
    public function __construct(DriverInterface $filesystem, $filePath = 'var/log/ingrammicroimport/') //@codingStandardsIgnoreLine
    {
        $filePath = BP . DIRECTORY_SEPARATOR . $filePath;
        parent::__construct($filesystem, $filePath);
    }
}
