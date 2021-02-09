<?php
namespace Mageseller\LeadersystemsImport\Helper\Product\Import\Image;

use Mageseller\Process\Model\Process as ProcessModel;
use Mageseller\Process\Model\ProcessFactory;
use Mageseller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Mageseller\LeadersystemsImport\Helper\Leadersystems as LeadersystemsHandler;

class Process
{
    /**
     * @var ProcessFactory
     */
    protected $processFactory;

    /**
     * @var ProcessResourceFactory
     */
    protected $processResourceFactory;

    /**
     * @param ProcessFactory         $processFactory
     * @param ProcessResourceFactory $processResourceFactory
     */
    public function __construct(
        ProcessFactory $processFactory,
        ProcessResourceFactory $processResourceFactory
    ) {
        $this->processFactory = $processFactory;
        $this->processResourceFactory = $processResourceFactory;
    }

    /**
     * Imports products from CM51 into Magento from specified process
     *
     * @param \DateTime $since
     */
    public function runApi($since)
    {
        /**
 * @var ProcessModel $process 
*/
        $process = $this->processFactory->create()
            ->setType('Leadersystems image import')
            ->setStatus(ProcessModel::STATUS_PENDING)
            ->setName('Leadersystems image import')
            ->setParams([$since])
            ->setHelper(LeadersystemsHandler::class)
            ->setMethod('importLeadersystemsImages');

        $this->processResourceFactory->create()->save($process);

        $process->run();
    }
}
