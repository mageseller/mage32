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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DickerdataCategoryImport extends Command
{
    /**
     * @var \Mageseller\DickerdataImport\Helper\Dickerdata
     */
    private $dickerdataHelper;

    /**
     * DickerdataCategoryImport constructor.
     *
     * @param string|null $name
     */
    public function __construct(\Mageseller\DickerdataImport\Helper\Dickerdata $dickerdataHelper, string $name = null)
    {
        $this->dickerdataHelper = $dickerdataHelper;
        parent::__construct($name);
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
        $this->dickerdataHelper->importDickerdataCategory();
        $output->writeln("All Dickerdata Category successfully imported ");
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("mageseller_dickerdataimport:dickerdatacategoryimport");
        $this->setDescription("Category Import Dickerdata");
        $this->setDefinition(
            [
            new InputArgument(self::NAME_ARGUMENT, InputArgument::OPTIONAL, "Name"),
            new InputOption(self::NAME_OPTION, "-a", InputOption::VALUE_NONE, "Option functionality")
            ]
        );
        parent::configure();
    }
}
