<?php

namespace FluxAPI\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Migrate extends Command
{
    protected function configure()
    {
        $this
        ->setName('migrate')
        ->setDescription('Migrate the storage(s)')
        ->addArgument(
            'model_name',
            InputArgument::OPTIONAL,
            'If set only this model will be migrated.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $model_name = $input->getArgument('model_name');

        $output->writeln(\FluxAPI\Api::getInstance()->migrate($model_name));
    }
}