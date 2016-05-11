<?php
namespace Poirot\Http\HttpMessage;

use Poirot\Http\HttpMessage\Interfaces\iPluginHttp;
use Poirot\Http\Interfaces\iHttpMessage;
use Poirot\Http\Interfaces\Message\iHttpRequest;
use Poirot\Ioc\Container\aContainerCapped;
use Poirot\Ioc\Container\BuilderContainer;
use Poirot\Ioc\Container\Exception\exContainerInvalidServiceType;

class PluginsHttp 
    extends aContainerCapped
    implements iPluginHttp   // itself must have message object
{
    /** @var iHttpMessage|iHttpRequest|iHttpMessage */
    protected $messageObject;

    
    /**
     * Construct
     *
     * @param BuilderContainer $cBuilder
     *
     * @throws \Exception
     */
    function __construct(BuilderContainer $cBuilder = null)
    {
        parent::__construct($cBuilder);

        // Add Initializer To Inject Http Message Instance:
        $thisContainer = $this;
        $this->initializer()->addCallable(function($service) use ($thisContainer) {
            // Inject Service Container Inside
            if ($service instanceof iPluginHttp)
                ##! initializer may run on services(iService) object itself.
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
        $this->messageObject = $httpMessage;
        return $this;
    }

    /**
     * Get Http Message
     *
     * @return iHttpMessage
     */
    function getMessageObject()
    {
        if (!$this->messageObject)
            throw new \RuntimeException('Message Object is mandatory but not injected.');

        return $this->messageObject;
    }
    
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
        if (!$pluginInstance instanceof iPluginHttp)
            throw new exContainerInvalidServiceType(
                'Invalid Plugin Provided Instance.'
            );
    }
}
