<?php
/**
 * A Magento 2 module named Mageseller/DickerdataImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/DickerdataImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\DickerdataImport\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Mageseller\DickerdataImport\Helper\Dickerdata;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Mageseller\Process\Console\Command\CommandTrait;
use Mageseller\DickerdataImport\Helper\Product\Import\Process as ImportProcess;

class DickerdataProductImport extends Command
{
    use CommandTrait;
    const UPDATED_SINCE_OPTION = 'since';
    /**
     * @var State
     */
    private $appState;

    /**
     * @var Dickerdata
     */
    private $dickerdataHelper;

    /**
     * @var ImportProcess
     */
    private $importProcess;

    /**
     * DickerdataCategoryImport constructor.
     * @param State $state
     * @param Dickerdata $dickerdataHelper
     * @param ImportProcess $importProcess
     * @param string|null $name
     */
    public function __construct(
        State $state,
        Dickerdata $dickerdataHelper,
        ImportProcess $importProcess,
        string $name = null
    ) {
        parent::__construct($name);
        $this->appState        = $state;
        $this->dickerdataHelper = $dickerdataHelper;
        $this->importProcess   = $importProcess;
    }

    const NAME_ARGUMENT = "name";
    const NAME_OPTION = "option";

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->setAreaCode(Area::AREA_ADMINHTML);
        $output->writeln('Importing Products ...');
        $updatedSince = $input->getOption(self::UPDATED_SINCE_OPTION);

        if (empty($updatedSince)) {
            $updatedSince = $this->dickerdataHelper->getSyncDate('product');
        } else {
            $updatedSince = new \DateTime($updatedSince);
        }
        $this->importProcess->runApi($updatedSince);
        //$output->writeln("All Dickerdata Products successfully imported ");
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::UPDATED_SINCE_OPTION,
                null,
                InputOption::VALUE_OPTIONAL,
                'Product: Import starting date. Given date must respect ISO-8601 format and must be URL encoded',
                null
            ),
        ];
        $this->setName("mageseller_dickerdataimport:dickerdataproductimport");
        $this->setDescription("Product Import Dickerdata");
        $this->setDefinition($options);
        parent::configure();
    }
}
