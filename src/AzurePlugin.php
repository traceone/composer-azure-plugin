<?php

namespace TraceOne\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\InstallerEvents;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;

use TraceOne\Composer\AzureRepository;

/**
 * @todo load packages on install/update only
 * @todo handle version modifiers
 * @todo avoid redownloading cached packages
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
    protected $cache_dir;

    /**
     * 
     */
    protected $repositories = [];

    /**
     * 
     */
    protected $require_download = false;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $extra = $composer->getPackage()->getExtra();
        
        if(!isset($extra['azure-repositories']) || !is_array($extra['azure-repositories']))
        {
            return;
        }
        
        $this->composer = $composer;
        $this->io = $io;
        $this->cache_dir = str_replace(DIRECTORY_SEPARATOR, '/', $this->composer->getConfig()->get('cache-dir')) . '/azure';

        $this->parseRequiredPackages();
        $this->fetchAzurePackages();
        $this->addAzureRepositories();
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
            // InstallerEvents::PRE_DEPENDENCIES_SOLVING   => [ [ 'fetchAzurePackages', 0 ] ],
            
            ScriptEvents::PRE_INSTALL_CMD   => [ [ 'requireDownload', 50000 ] ],
            ScriptEvents::PRE_UPDATE_CMD    => [ [ 'requireDownload', 50000 ] ]
        ];
    }

    /**
     * Set flag to activate artifacts download from Azure
     */
    public function requireDownload()
    {
        $this->require_download = true;
    }

    /**
     * Initiate download if needed
     */
    public function fetchAzurePackages()
    {
        // if(!$this->require_download)
        // {
        //     return;
        // }

        $package_count = 0;

        foreach($this->repositories as $azure_repository)
        {
            $package_count+= $azure_repository->countArtifacts();
        }

        if($package_count == 0)
        {
            return;
        }

        $this->io->write('');
        $this->io->write('<info>Fecthing packages from Azure</info>');
        $this->downloadAzureArtifacts();
    }

    /**
     * Parse required Azure packages
     */
    protected function parseRequiredPackages()
    {
        $extra = $this->composer->getPackage()->getExtra();
        $requires = $this->composer->getPackage()->getRequires();

        foreach($extra['azure-repositories'] as [ 'organization' => $organization, 'feed' => $feed, 'packages' => $packages ])
        {
            $azure_repository = new AzureRepository($organization, $feed);

            foreach($packages as $package_name)
            {
                if(array_key_exists($package_name, $requires))
                {
                    $azure_repository->addArtifact($package_name, $requires[$package_name]->getPrettyConstraint());
                }
            }

            $this->repositories[] = $azure_repository;
        }
    }

    /**
     * Add repositories to Composer config
     */
    protected function addAzureRepositories()
    {
        $repositories = [];
        
        foreach($this->repositories as $azure_repository)
        {
            $organization = $azure_repository->getOrganization();
            $feed = $azure_repository->getFeed();
            
            foreach($azure_repository->getArtifacts() as $artifact)
            {
                array_unshift($repositories, [
                    'type'      => 'path',
                    'url'       => implode('/', [ $this->cache_dir, $organization, $feed, $artifact['name'], $artifact['version'] ]),
                    'options'   => [ 'symlink' =>  false ]
                ]);
            }
        }

        $this->composer->getConfig()->merge(['repositories' => $repositories]);
    }

    /**
     * Download artifacts
     */
    protected function downloadAzureArtifacts()
    {
        foreach($this->repositories as $azure_repository)
        {
            $organization = $azure_repository->getOrganization();
            $feed = $azure_repository->getFeed();
            $artifacts = $azure_repository->getArtifacts();

            foreach($artifacts as $artifact)
            {
                $path = implode('/', [ $this->cache_dir, $organization, $feed, $artifact['name'], $artifact['version'] ]);
                $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

                $command = 'az artifacts universal download';
                $command.= ' --organization ' . 'https://' . $organization;
                $command.= ' --feed ' . $feed;
                $command.= ' --name ' . str_replace('/', '.', $artifact['name']);
                $command.= ' --version ' . $artifact['version'];
                $command.= ' --path ' . $path;

                exec($command);
            }
        }
    }
}