<?php
/**
 * HandlerAbstract
 *
 * @copyright Copyright © 2020 Mageseller. All rights reserved.
 * @author    satish29g@hotmail.com
 */

namespace  Mageseller\DriveFx\Logger\Handler;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;

class HandlerAbstract extends Base
{
    /**
     * HandlerAbstract constructor.
     *
     * Set default filePath for DrivefxLogger logs folder
     *
     * @param DriverInterface $filesystem
     * @param null|string $filePath
     */
    public function __construct(DriverInterface $filesystem, $filePath = 'var/log/drivefxlogger/') //@codingStandardsIgnoreLine
    {
        $filePath = BP . DIRECTORY_SEPARATOR . $filePath;
        parent::__construct($filesystem, $filePath);
    }
}
