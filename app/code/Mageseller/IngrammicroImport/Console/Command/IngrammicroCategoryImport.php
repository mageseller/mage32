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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IngrammicroCategoryImport extends Command
{
    /**
     * @var \Mageseller\IngrammicroImport\Helper\Ingrammicro
     */
    private $ingrammicroHelper;

    /**
     * IngrammicroCategoryImport constructor.
     *
     * @param string|null $name
     */
    public function __construct(\Mageseller\IngrammicroImport\Helper\Ingrammicro $ingrammicroHelper, string $name = null)
    {
        $this->ingrammicroHelper = $ingrammicroHelper;
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
        $this->ingrammicroHelper->importIngrammicroCategory();
        $output->writeln("All Ingrammicro Category successfully imported ");
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("mageseller_ingrammicroimport:ingrammicrocategoryimport");
        $this->setDescription("Category Import Ingrammicro");
        $this->setDefinition(
            [
            new InputArgument(self::NAME_ARGUMENT, InputArgument::OPTIONAL, "Name"),
            new InputOption(self::NAME_OPTION, "-a", InputOption::VALUE_NONE, "Option functionality")
            ]
        );
        parent::configure();
    }
}
