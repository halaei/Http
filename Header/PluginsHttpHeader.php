<?php
namespace Poirot\Http\Header;

use Poirot\Http\Interfaces\iHeader;
use Poirot\Ioc\Container\aContainerCapped;
use Poirot\Ioc\Container\Exception\exContainerInvalidServiceType;

class PluginsHttpHeader 
    extends aContainerCapped
{
    /**
     * Validate Plugin Instance Object
     *
     * @param mixed $pluginInstance
     *
     * @throws exContainerInvalidServiceType
     * @return void
     */
    function validateService($pluginInstance)
    {
        if (!$pluginInstance instanceof iHeader)
            throw new exContainerInvalidServiceType(
                'Invalid Plugin Of Header Instance Provided.'
            );
    }
}
