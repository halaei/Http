<?php
namespace Poirot\Http\Plugins;

use Poirot\Container\Exception\ContainerInvalidPluginException;
use Poirot\Container\Interfaces\iContainerBuilder;
use Poirot\Container\Interfaces\Respec\iCServiceAware;
use Poirot\Container\Plugins\AbstractPlugins;
use Poirot\Http\Interfaces\Message\iHttpMessage;

class HttpPluginsManager extends AbstractPlugins
    implements iHttpPlugin
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
     * Set Http Message Object (Request|Response)
     *
     * note: so services can have access to http message instance
     *
     * @param iHttpMessage $httpMessage
     *
     * @return $this
     */
    function setMessageObject(iHttpMessage $httpMessage)
    {
        $this->_mess_object = $httpMessage;

        return $this;
    }

    /**
     * Get Http Message
     *
     * @return iHttpMessage
     */
    function getMessageObject()
    {
        return $this->_mess_object;
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