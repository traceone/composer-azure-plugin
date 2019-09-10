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
use TraceOne\Composer\Helpers;

/**
 * @todo use Composer cache folder
 * @todo handle version modifiers
 * @todo clean vendor file when removing an Azure package
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
     * 
     */
    protected $require_download = false;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $extra = $composer->getPackage()->getExtra();
        
        if(!isset($extra['azure-repositories']) && !is_array($extra['azure-repositories']))
        {
            return;
        }
        
        $this->composer = $composer;
        $this->io = $io;

        $this->parseRequiredPackages();
        $this->addAzureRepositories(false);
        
        // TEMP: Remove my-package from requires
        // $requires = $this->composer->getPackage()->getRequires();
        // unset($requires['trace-one/my-package']);
        // $this->composer->getPackage()->setRequires($requires);
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
            ScriptEvents::PRE_INSTALL_CMD   => [ [ 'requireDownload', 0 ] ],
            ScriptEvents::PRE_UPDATE_CMD    => [ [ 'requireDownload', 0 ] ],
            ScriptEvents::POST_INSTALL_CMD  => [ [ 'clean', 0 ] ],
            ScriptEvents::POST_UPDATE_CMD   => [ [ 'clean', 0 ] ],

            InstallerEvents::PRE_DEPENDENCIES_SOLVING => [ [ 'fetchAzurePackages', 0 ] ]
        ];
    }

    public function clean()
    {
        if(file_exists('./.azure'))
        {
            Helpers::removeDirectory('./.azure');
        }
    }

    /**
     * 
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
        if(!$this->require_download)
        {
            return;
        }

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
        $this->downloadAzureArtifacts(false);
    }

    /**
     * Parse Azure artifacts required by the package
     */
    protected function parseRequiredPackages()
    {
        $extra = $this->composer->getPackage()->getExtra();
        
        if(!isset($extra['azure-repositories']) && !is_array($extra['azure-repositories']))
        {
            return;
        }

        $requires = $this->composer->getPackage()->getRequires();

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
     * Download artifacts to .azure
     */
    protected function downloadAzureArtifacts($use_compression)
    {
        foreach($this->repositories as $azure_repository)
        {
            $organization = $azure_repository->getOrganization();
            $feed = $azure_repository->getFeed();
            $artifacts = $azure_repository->getArtifacts();

            foreach($artifacts as $artifact)
            {
                $repository_path = '.' . DIRECTORY_SEPARATOR .'.azure' . DIRECTORY_SEPARATOR . $organization . DIRECTORY_SEPARATOR . $feed;
                $artifact_path = $repository_path . DIRECTORY_SEPARATOR . $artifact['name'];

                $command = 'az artifacts universal download';
                $command.= ' --organization ' . 'https://' . $organization;
                $command.= ' --feed ' . $feed;
                $command.= ' --name ' . $artifact['name'];
                $command.= ' --version ' . $artifact['version'];
                $command.= ' --path ' . $artifact_path;

                shell_exec($command);

                if($use_compression)
                {
                    Helpers::buildArchive($artifact_path);
                    Helpers::removeDirectory($artifact_path);
                }
            }
        }
    }

    /**
     * Add repositories to Composer config
     */
    protected function addAzureRepositories($use_compression)
    {
        $repositories = [];
        
        foreach($this->repositories as $azure_repository)
        {
            $organization = $azure_repository->getOrganization();
            $feed = $azure_repository->getFeed();
            
            if($use_compression)
            {
                array_unshift($repositories, [
                    'type' => 'artifact',
                    'url' => './.azure/' . $organization . '/' . $feed
                ]);
            }
            else
            {
                foreach($azure_repository->getArtifacts() as $artifact)
                {
                    array_unshift($repositories, [
                        'type'      => 'path',
                        'url'       => './.azure/' . $organization . '/' . $feed . '/' . $artifact['name'],
                        'options'   => [ 'symlink' =>  false ]
                    ]);
                }
            }
        }

        $this->composer->getConfig()->merge(['repositories' => $repositories]);
    }
}