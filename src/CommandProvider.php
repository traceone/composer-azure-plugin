<?php

namespace TraceOne\Composer;

use TraceOne\Composer\Command\PublishCommand;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

class CommandProvider implements CommandProviderCapability
{
    /**
     * {@inheritdoc}
     */
    public function getCommands()
    {
        return [ new PublishCommand() ];
    }
}