<?php
namespace Poirot\Http\Header;

use Poirot\Container\Exception\ContainerInvalidPluginException;
use Poirot\Container\Plugins\AbstractPlugins;
use Poirot\Http\Interfaces\iHeader;

class PluginsHttpHeader extends AbstractPlugins
{
    /**
     * Validate Plugin Instance Object
     *
     * @param mixed $pluginInstance
     *
     * @throws ContainerInvalidPluginException
     * @return void
     */
    function validatePlugin($pluginInstance)
    {
        if (!$pluginInstance instanceof iHeader)
            throw new ContainerInvalidPluginException(
                'Invalid Plugin Provided Instance.'
            );
    }
}
