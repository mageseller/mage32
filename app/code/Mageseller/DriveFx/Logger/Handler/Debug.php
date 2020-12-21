<?php
/**
 * Debug
 *
 * @copyright Copyright © 2020 Mageseller. All rights reserved.
 * @author    satish29g@hotmail.com
 */

namespace  Mageseller\DriveFx\Logger\Handler;

use Monolog\Logger;

class Debug extends HandlerAbstract
{
    /**
     * @var string
     */
    protected $fileName = 'debug.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
}
