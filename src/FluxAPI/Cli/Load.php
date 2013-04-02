<?php

namespace FluxAPI\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Load extends Command
{
    protected function configure()
    {
        $this
        ->setName('load')
        ->setDescription('Load a model')
        ->addArgument(
            'model_name',
            InputArgument::REQUIRED,
            'What kind of Model you want to load?'
        )
        ->addArgument(
            'id',
            InputArgument::REQUIRED,
            'ID of the model to load'
        )
        ->addOption(
            'format',
            \FluxAPI\Api::DATA_FORMAT_JSON,
            InputOption::VALUE_REQUIRED,
            'The output data format. Default "json". Can be "json", "xml", "yaml", "array" or "object"'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $model_name = $input->getArgument('model_name');
        $id = $input->getArgument('id');

        $load_method = 'load'.$model_name;

        $output->writeln(\FluxAPI\Api::getInstance()->$load_method($id));
    }
}