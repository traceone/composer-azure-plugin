<?php

namespace TraceOne\Composer\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use TraceOne\Composer\Helpers;

/**
 * @todo Handle properly .gitignore
 */
class PublishCommand extends BaseCommand
{
    /**
     * 
     */
    protected $temp_dir = '../.temp';

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

        $this->copyPackage();
        $this->cleanIgnoredFiles();
        $this->sendPackage();
        $this->removeTempFiles();

        $output->writeln('Done.');
    }

    /**
     * 
     */
    protected function copyPackage()
    {
        Helpers::copyDirectory('.', $this->temp_dir);
    }

    /**
     *
     */
    protected function cleanIgnoredFiles()
    {
        if(!file_exists($this->temp_dir . '/.gitignore'))
        {
            return;
        }

        $ignored_files = file($this->temp_dir . '/.gitignore');

        if($ignored_files === false)
        {
            return;
        }

        foreach($ignored_files as $ignored_file)
        {
            if(is_dir($this->temp_dir . $ignored_file))
            {
                Helpers::removeDirectory($this->temp_dir . $ignored_file);
            }
        }
    }

    /*
     *
     */
    protected function sendPackage()
    {
        $extra = $this->getComposer()->getPackage()->getExtra();

        $command = 'az artifacts universal publish';
        $command.= ' --organization ' . 'https://' . $extra['azure-publish-registry']['organization'];
        $command.= ' --feed ' . $extra['azure-publish-registry']['feed'];
        $command.= ' --name ' . str_replace('/', '.', $this->getComposer()->getPackage()->getName());
        $command.= ' --version ' . $this->getComposer()->getPackage()->getPrettyVersion();
        $command.= ' --description "' . $this->getComposer()->getPackage()->getDescription() . '"';
        $command.= ' --path ' . $this->temp_dir;

        shell_exec($command);
    }

    /**
     * 
     */
    protected function removeTempFiles()
    {
        Helpers::removeDirectory($this->temp_dir);
    }
}