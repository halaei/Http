<?php
namespace Poirot\Http\HttpMessage\Request\Plugin;

use Poirot\Http\HttpMessage\Interfaces\iPluginHttp;
use Poirot\Http\Interfaces\iHttpMessage;
use Poirot\Http\Interfaces\iHttpRequest;

/*
 * Plugin::_($request)->helperMethod();
 * 
 */

class aPluginRequest
    implements iPluginHttp
{
    /** @var iHttpRequest */
    protected $messageObject;


    /**
     * Wrapper Identifier Around Http Message
     *
     * @param iHttpRequest $httpRequest
     * 
     * @return static
     */
    static function _(iHttpRequest $httpRequest)
    {
        $plugin = new static;
        $plugin->setMessageObject($httpRequest);
        return $plugin;
    }

    // Implement iHttpPlugin

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
        if (!$httpMessage instanceof iHttpRequest)
            throw new \InvalidArgumentException(sprintf(
                'This plugin need request object instance of iHttpRequest, "%s" given.'
                , get_class($httpMessage)
            ));

        $this->messageObject = $httpMessage;
        return $this;
    }

    /**
     * Get Http Message
     *
     * @return iHttpRequest
     */
    function getMessageObject()
    {
        return $this->messageObject;
    }
}
