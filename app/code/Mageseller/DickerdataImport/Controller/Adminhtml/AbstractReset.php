<?php
namespace Mageseller\DickerdataImport\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Mageseller\Utility\Helper\Data as ConnectorConfig;
use Mageseller\Process\Controller\Adminhtml\RedirectRefererTrait;

abstract class AbstractReset extends Action
{
    use RedirectRefererTrait;

    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mageseller_Config::sync';

    /**
     * @var ConnectorConfig
     */
    protected $connectorConfig;

    /**
     * @param Context         $context
     * @param ConnectorConfig $connectorConfig
     */
    public function __construct(
        Context $context,
        ConnectorConfig $connectorConfig
    ) {
        parent::__construct($context);
        $this->connectorConfig = $connectorConfig;
    }
}
