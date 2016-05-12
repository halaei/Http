<?php
namespace Poirot\Http\Header;

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
        if (!$pluginInstance instanceof aHeaderHttp)
            throw new exContainerInvalidServiceType(
                'Invalid Plugin Of Header Instance Provided.'
            );
    }
}
