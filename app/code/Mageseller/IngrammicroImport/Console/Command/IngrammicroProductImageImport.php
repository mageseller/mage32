<?php
/**
 * A Magento 2 module named Mageseller/IngrammicroImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/IngrammicroImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\IngrammicroImport\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Mageseller\IngrammicroImport\Helper\Product\Import\Image\Process as ImportImageProcess;
use Mageseller\Process\Console\Command\CommandTrait;
use Mageseller\Utility\Helper\Data;

class IngrammicroProductImageImport extends Command
{
    use CommandTrait;
    const UPDATED_SINCE_OPTION = 'since';
    /**
     * @var State
     */
    private $appState;

    /**
     * @var Data
     */
    private $utilityHelper;

    /**
     * @var ImportImageProcess
     */
    private $importImageProcess;

    /**
     * IngrammicroCategoryImport constructor.
     *
     * @param State              $state
     * @param Data        $utilityHelper
     * @param ImportImageProcess $importImageProcess
     * @param string|null        $name
     */
    public function __construct(
        State $state,
        Data $utilityHelper,
        ImportImageProcess $importImageProcess,
        string $name = null
    ) {
        parent::__construct($name);
        $this->appState        = $state;
        $this->utilityHelper = $utilityHelper;
        $this->importImageProcess   = $importImageProcess;
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
        $output->writeln('Importing Images ...');
        $updatedSince = $input->getOption(self::UPDATED_SINCE_OPTION);

        if (empty($updatedSince)) {
            $updatedSince = $this->utilityHelper->getSyncDate('ingrammicro', 'images');
        } else {
            $updatedSince = new \DateTime($updatedSince);
        }
        $this->importImageProcess->runApi($updatedSince);
        //$output->writeln("All Ingrammicro Products successfully imported ");
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
        $this->setName("mageseller_ingrammicroimport:ingrammicroproductimageimport");
        $this->setDescription("Product Image Import Ingrammicro");
        $this->setDefinition($options);
        parent::configure();
    }
}
