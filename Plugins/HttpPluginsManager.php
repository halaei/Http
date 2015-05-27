<?php
namespace Poirot\Http\Plugins;

use Poirot\Container\Exception\ContainerInvalidPluginException;
use Poirot\Container\Plugins\AbstractPlugins;
use Poirot\Http\Interfaces\Message\iHttpMessage;

class HttpPluginsManager extends AbstractPlugins
{
    /**
     * @var iHttpMessage
     */
    protected $_mess_object;

    /**
     * Set Http Message Object (Request|Response)
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
        if (!is_object($pluginInstance))
            throw new ContainerInvalidPluginException(
                'Invalid Plugin Instance.'
            );
    }
}
