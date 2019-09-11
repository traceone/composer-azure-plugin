<?php

namespace TraceOne\Composer;

/**
 * 
 */
class AzureRepository
{
    /**
     * 
     */
    protected $organization;

    /**
     * 
     */
    protected $feed;

    /**
     * 
     */
    protected $artifacts = [];

    /**
     * 
     */
    public function __construct(String $organization, String $feed)
    {
        $this->organization = $organization;
        $this->feed = $feed;
    }

    /**
     * 
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * 
     */
    public function getFeed()
    {
        return $this->feed;
    }

    /**
     * 
     */
    public function addArtifact(String $name, String $version)
    {
        $this->artifacts[] = [
            'name' => $name,
            'version' => $version
        ];
    }

    /**
     * 
     */
    public function getArtifacts(): Array
    {
        return $this->artifacts;
    }

    /**
     *
     */
    public function countArtifacts(): Int
    {
        return count($this->artifacts);
    }
}