<?php
/**
 * DickerdataImport
 *
 * @copyright Copyright © 2020 Mageseller. All rights reserved.
 * @author    satish29g@hotmail.com
 */

namespace Mageseller\DickerdataImport\Logger;

use Monolog\Logger;
use Mageseller\DickerdataImport\Logger\Handler\HandlerFactory;

class DickerdataImport extends Logger
{
    /**
     * @var array
     */
    protected $defaultHandlerTypes = [
        'error',
        'info',
        'debug'
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(
        HandlerFactory $handlerFactory,
        $name = 'dickerdataimport',
        array $handlers = [],
        array $processors = []
    ) {
        foreach ($this->defaultHandlerTypes as $handlerType) {
            if (!array_key_exists($handlerType, $handlers)) {
                $handlers[$handlerType] = $handlerFactory->create($handlerType);
            }
        }
        parent::__construct($name, $handlers, $processors);
    }

}
