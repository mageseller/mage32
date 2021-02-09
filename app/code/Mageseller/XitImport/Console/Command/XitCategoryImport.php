<?php
/**
 * A Magento 2 module named Mageseller/XitImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/XitImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\XitImport\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class XitCategoryImport extends Command
{
    /**
     * @var \Mageseller\XitImport\Helper\Xit
     */
    private $xitHelper;

    /**
     * XitCategoryImport constructor.
     *
     * @param string|null $name
     */
    public function __construct(\Mageseller\XitImport\Helper\Xit $xitHelper, string $name = null)
    {
        $this->xitHelper = $xitHelper;
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
        $this->xitHelper->importXitCategory();
        $output->writeln("All Xit Category successfully imported ");
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("mageseller_xitimport:xitcategoryimport");
        $this->setDescription("Category Import Xit");
        $this->setDefinition(
            [
            new InputArgument(self::NAME_ARGUMENT, InputArgument::OPTIONAL, "Name"),
            new InputOption(self::NAME_OPTION, "-a", InputOption::VALUE_NONE, "Option functionality")
            ]
        );
        parent::configure();
    }
}
