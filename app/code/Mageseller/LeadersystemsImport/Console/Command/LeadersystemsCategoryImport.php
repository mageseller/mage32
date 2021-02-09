<?php
/**
 * A Magento 2 module named Mageseller/LeadersystemsImport
 * Copyright (C) 2019
 *
 * This file included in Mageseller/LeadersystemsImport is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\LeadersystemsImport\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LeadersystemsCategoryImport extends Command
{
    /**
     * @var \Mageseller\LeadersystemsImport\Helper\Leadersystems
     */
    private $leadersystemsHelper;

    /**
     * LeadersystemsCategoryImport constructor.
     *
     * @param string|null $name
     */
    public function __construct(\Mageseller\LeadersystemsImport\Helper\Leadersystems $leadersystemsHelper, string $name = null)
    {
        $this->leadersystemsHelper = $leadersystemsHelper;
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
        $this->leadersystemsHelper->importLeadersystemsCategory();
        $output->writeln("All Leadersystems Category successfully imported ");
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("mageseller_leadersystemsimport:leadersystemscategoryimport");
        $this->setDescription("Category Import Leadersystems");
        $this->setDefinition(
            [
            new InputArgument(self::NAME_ARGUMENT, InputArgument::OPTIONAL, "Name"),
            new InputOption(self::NAME_OPTION, "-a", InputOption::VALUE_NONE, "Option functionality")
            ]
        );
        parent::configure();
    }
}
