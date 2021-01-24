<?php
namespace Mageseller\LeadersystemsImport\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Mageseller\Process\Controller\Adminhtml\RawMessagesTrait;
use Mageseller\Process\Controller\Adminhtml\RedirectRefererTrait;
use Mageseller\Process\Model\ProcessFactory;
use Mageseller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Psr\Log\LoggerInterface;

abstract class AbstractSync extends Action
{
    use RedirectRefererTrait;
    use RawMessagesTrait;

    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mageseller_Config::sync';

    /**
     * @var ApiConfig
     */
    protected $apiConfig;

    /**
     * @var ProcessFactory
     */
    protected $processFactory;

    /**
     * @var ProcessResourceFactory
     */
    protected $processResourceFactory;

    /**
     * @var ConnectorConfig
     */
    protected $connectorConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param ProcessFactory $processFactory
     * @param ProcessResourceFactory $processResourceFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        ProcessFactory $processFactory,
        ProcessResourceFactory $processResourceFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->processFactory = $processFactory;
        $this->processResourceFactory = $processResourceFactory;
        $this->logger = $logger;
    }

    /**
     * Will redirect with an error if Mageseller Connector is disabled in config
     *
     * @return  bool
     */
    protected function checkConnectorEnabled()
    {
        return true;
    }
}
