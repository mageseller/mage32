<?php
/*
 * A Magento 2 module named Mageseller/DriveFx
 * Copyright (C) 2020
 *
 *  @author      satish29g@hotmail.com
 *  @site        https://www.mageseller.com/
 *
 * This file included in Mageseller/DriveFx is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 *
 */

namespace  Mageseller\DriveFx\Logger\Handler;

use InvalidArgumentException;
use Magento\Framework\ObjectManagerInterface;
use  Mageseller\DriveFx\Logger\Handler\HandlerAbstract as ObjectType;

class HandlerFactory
{
    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $instanceTypeNames = [
        'error' => \Mageseller\DriveFx\Logger\Handler\Error::class,
        'info' => \Mageseller\DriveFx\Logger\Handler\Info::class,
        'debug' => \Mageseller\DriveFx\Logger\Handler\Debug::class,
    ];

    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create corresponding class instance
     *
     * @param $type
     * @param array $data
     * @return ObjectType
     */
    public function create($type, array $data = [])
    {
        if (empty($this->instanceTypeNames[$type])) {
            throw new InvalidArgumentException('"' . $type . ': isn\'t allowed');
        }

        $resultInstance = $this->objectManager->create($this->instanceTypeNames[$type], $data);
        if (!$resultInstance instanceof ObjectType) {
            throw new InvalidArgumentException(get_class($resultInstance) .
                ' isn\'t instance of \Mageseller\DriveFx\Logger\Handler\HandlerAbstract');
        }

        return $resultInstance;
    }
}