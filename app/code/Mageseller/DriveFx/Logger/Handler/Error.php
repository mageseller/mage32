<?php
/**
 * Error
 *
 * @copyright Copyright © 2020 Mageseller. All rights reserved.
 * @author    satish29g@hotmail.com
 */

namespace  Mageseller\DriveFx\Logger\Handler;

use Monolog\Logger;

class Error extends HandlerAbstract
{
    /**
     * @var string
     */
    protected $fileName = 'error.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::ERROR;
}
