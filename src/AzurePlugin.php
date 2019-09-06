<?php

namespace TraceOne\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;

use TraceOne\Composer\AzureRepository;
use TraceOne\Composer\Helpers;

// use Composer\Installer\PackageEvents;
// use Composer\Installer\InstallerEvents;
// use Composer\Script\ScriptEvents;

/**
 * @todo use Composer cache folder
 * @todo handle version modifiers
 */
class AzurePlugin implements PluginInterface, EventSubscriberInterface, Capable
{
    /**
     * 
     */
    protected $composer;

    /**
     * 
     */
    protected $io;

    /**
     * 
     */
    protected $repositories = [];

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;

        $this->package = $composer->getPackage();
        $this->io = $io;
    }

    /**
     * {@inheritdoc}
     */
    public function getCapabilities()
    {
        return array(
            'Composer\Plugin\Capability\CommandProvider' => 'TraceOne\Composer\CommandProvider',
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PluginEvents::INIT => [ [ 'onInit', 0 ] ]
        ];
    }

    /**
     * 
     */
    public function onInit()
    {
        $this->io->write('Running Azure plugin: init');

        $extra = $this->package->getExtra();
        
        if(!isset($extra['azure-repositories']) && !is_array($extra['azure-repositories']))
        {
            return;
        }

        print_r($this->package->getRepositories());
        
        // $this->parseRequiredPackages();
        // $this->downloadArtifacts();
        // $this->addArtifactRepositories();
        
        // Parse Azure artifacts
        // Download artifacts to .artifacts
        // Add artifact repositories programatically
        
        
        // Let Composer install the downloaded artifacts
        // Delete the temp .artifacts folder
        
        // TEMP: Remove my-package from requires
        // $requires = $this->package->getRequires();
        // unset($requires['trace-one/my-package']);
        // $this->package->setRequires($requires);
    }

    /**
     * 
     */
    protected function parseRequiredPackages()
    {
        $this->io->write('Running Azure plugin: parseRequiredPackages');

        $extra = $this->package->getExtra();
        
        if(!isset($extra['azure-repositories']) && !is_array($extra['azure-repositories']))
        {
            return;
        }

        $requires = $this->package->getRequires();

        foreach($extra['azure-repositories'] as [ 'organization' => $organization, 'feed' => $feed, 'artifacts' => $artifacts ])
        {
            $azure_repository = new AzureRepository($organization, $feed);

            foreach($artifacts as $artifact_name => $package_name)
            {
                if(array_key_exists($package_name, $requires))
                {
                    $azure_repository->addArtifact($artifact_name, $requires[$package_name]->getPrettyConstraint());
                }
            }

            $this->repositories[] = $azure_repository;
        }
    }

    /**
     * 
     */
    protected function downloadArtifacts()
    {
        $this->io->write('Running Azure plugin: downloadArtifacts');

        foreach($this->repositories as $azure_repository)
        {
            $organization = $azure_repository->getOrganization();
            $feed = $azure_repository->getFeed();
            $artifacts = $azure_repository->getArtifacts();

            foreach($artifacts as $artifact)
            {
                $repository_path = '.' . DIRECTORY_SEPARATOR .'.artifacts' . DIRECTORY_SEPARATOR . $organization . DIRECTORY_SEPARATOR . $feed;
                $artifact_path = $repository_path . DIRECTORY_SEPARATOR . $artifact['name'];

                $command = 'az artifacts universal download';
                $command.= ' --organization ' . 'https://' . $organization;
                $command.= ' --feed ' . $feed;
                $command.= ' --name ' . $artifact['name'];
                $command.= ' --version ' . $artifact['version'];
                $command.= ' --path ' . $artifact_path;

                exec($command);

                Helpers::buildArchive($artifact_path);
                Helpers::removeDirectory($artifact_path);
            }
        }
    }

    /**
     * 
     */
    protected function addArtifactRepositories()
    {
        $this->io->write('Running Azure plugin: addArtifactRepositories');

        $repositories = $this->package->getRepositories();
        
        foreach($this->repositories as $azure_repository)
        {
            $organization = $azure_repository->getOrganization();
            $feed = $azure_repository->getFeed();
            
            $repositories[] = [
                'type' => 'artifact',
                'url' => '.' . DIRECTORY_SEPARATOR . '.artifacts' . DIRECTORY_SEPARATOR . $organization . DIRECTORY_SEPARATOR . $feed
            ];
        }

        $this->package->setRepositories($repositories);
    }
}