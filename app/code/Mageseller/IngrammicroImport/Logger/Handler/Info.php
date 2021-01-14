<?php
/**
 * Info
 *
 * @copyright Copyright © 2020 Mageseller. All rights reserved.
 * @author    satish29g@hotmail.com
 */

namespace Mageseller\IngrammicroImport\Logger\Handler;

use Monolog\Logger;

class Info extends HandlerAbstract
{
    /**
     * @var string
     */
    protected $fileName = 'info.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;
}
