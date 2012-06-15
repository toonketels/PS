<?php

namespace PS\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetupCommand extends Command
{
  protected function configure()
  {
    /**
     * Usage:
     *
     * ./console setup name="new_project"
     */
    $this->setName('setup')
         ->setDescription('Setup of local project environment.')
         ->addArgument('name', InputArgument::REQUIRED, 'The projects name.');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $name = $input->getArgument('name');

    $output->writeln($name);
  }
}
