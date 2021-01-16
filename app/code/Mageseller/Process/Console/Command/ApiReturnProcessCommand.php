<?php
namespace Mageseller\Process\Console\Command;

use Mageseller\Process\Helper\Data as Helper;
use Mageseller\Process\Model\Process;
use Mageseller\Process\Model\ProcessFactory;
use Mageseller\Process\Model\ResourceModel\ProcessFactory as ProcessResourceFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ApiReturnProcessCommand extends Command
{
    /**
     * Run specific id key
     */
    const RUN_PROCESS_OPTION = 'run';

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @var ProcessResourceFactory
     */
    protected $processResourceFactory;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @param   ProcessFactory          $processFactory
     * @param   ProcessResourceFactory  $processResourceFactory
     * @param   Helper                  $helper
     * @param   string|null             $name
     */
    public function __construct(
        ProcessFactory $processFactory,
        ProcessResourceFactory $processResourceFactory,
        Helper $helper,
        $name = null
    ) {
        parent::__construct($name);
        $this->processFactory = $processFactory;
        $this->processResourceFactory = $processResourceFactory;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::RUN_PROCESS_OPTION,
                null,
                InputOption::VALUE_REQUIRED,
                'Execute a specific process id'
            ),
        ];

        $this->setName('mageseller:process:api')
            ->setDescription('Handles Mageseller Api return processes execution')
            ->setDefinition($options);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($processId = $input->getOption(self::RUN_PROCESS_OPTION)) {
            $process = $this->processFactory->create();
            $this->processResourceFactory->create()->load($process, $processId);
            if (!$process->getId()) {
                throw new \InvalidArgumentException('This process no longer exists.');
            }
            if (!$process->canCheckMagesellerStatus()) {
                throw new \Exception('Mageseller status cannot be checked on this process.');
            }
            $process->addOutput('cli');
            $process->checkMagesellerStatus();
        } else {
            $processes = $this->helper->getMagesellerStatusToCheckProcesses();
            if ($processes->count() > 0) {
                foreach ($processes as $process) {
                    /** @var Process $process */
                    $output->writeln(sprintf('<info>Processing API Status #%s %s</info>', $process->getId(), $process->getName()));
                    $process->addOutput('cli');
                    $process->checkMagesellerStatus();
                }
            } else {
                $output->writeln('<error>Nothing to be processed</error>');
            }
        }
    }
}
