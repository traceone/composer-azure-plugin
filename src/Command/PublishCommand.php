<?php

namespace TraceOne\Composer\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @todo Remove vendor directory
 */
class PublishCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azure:publish');
        $this->setDescription('Publish this composer package to Azure DevOps.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extra = $this->getComposer()->getPackage()->getExtra();
        
        if(!isset($extra['azure-publish-registry']) || !is_array($extra['azure-publish-registry']))
        {
            return;
        }

        $command = 'az artifacts universal publish';
        $command.= ' --organization ' . 'https://' . $extra['azure-publish-registry']['organization'];
        $command.= ' --feed ' . $extra['azure-publish-registry']['feed'];
        $command.= ' --name ' . str_replace('/', '.', $this->getComposer()->getPackage()->getName());
        $command.= ' --version ' . $this->getComposer()->getPackage()->getPrettyVersion();
        $command.= ' --description "' . $this->getComposer()->getPackage()->getDescription() . '"';
        $command.= ' --path .';

        shell_exec($command);

        $output->writeln('Done.');
    }
}