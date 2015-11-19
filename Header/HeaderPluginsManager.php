<?php
namespace Poirot\Http\Header;

use Poirot\Container\Exception\ContainerInvalidPluginException;
use Poirot\Container\Interfaces\iContainerBuilder;
use Poirot\Container\Plugins\AbstractPlugins;
use Poirot\Http\Interfaces\Message\iHttpMessage;

class HeaderPluginsManager extends AbstractPlugins
{
    /**
     * @var iHttpMessage
     */
    protected $_mess_object;

    /**
     * @override
     *
     * Construct
     *
     * @param iContainerBuilder $cBuilder
     *
     * @throws \Exception
     */
    function __construct(iContainerBuilder $cBuilder = null)
    {
        parent::__construct($cBuilder);

        // Add Initializer To Inject Http Message Instance:
        $thisContainer = $this;
        $this->initializer()->addMethod(function() use ($thisContainer) {
            // Inject Service Container Inside
            $this->setMessageObject($thisContainer->getMessageObject());
        }, 10000);
    }

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
        if (!$pluginInstance instanceof iHttpPlugin)
            throw new ContainerInvalidPluginException(
                'Invalid Plugin Provided Instance.'
            );
    }
}
