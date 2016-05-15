<?php
namespace Poirot\Http\HttpMessage\Response\Plugin;

use Poirot\Http\HttpMessage\Interfaces\iPluginHttp;
use Poirot\Http\Interfaces\iHttpMessage;
use Poirot\Http\Interfaces\iHttpResponse;

class aPluginResponse
    implements iPluginHttp
{
    /** @var iHttpResponse */
    protected $messageObject;


    /**
     * Wrapper Identifier Around Http Message
     *
     * @param iHttpResponse $httpResponse
     * 
     * @return static
     */
    static function _(iHttpResponse $httpResponse)
    {
        $plugin = new static;
        $plugin->setMessageObject($httpResponse);
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
        if (!$httpMessage instanceof iHttpResponse)
            throw new \InvalidArgumentException(sprintf(
                'This plugin need response object instance of iHttpResponse, "%s" given.'
                , get_class($httpMessage)
            ));

        $this->messageObject = $httpMessage;
        return $this;
    }

    /**
     * Get Http Message
     *
     * @return iHttpResponse
     */
    function getMessageObject()
    {
        return $this->messageObject;
    }
}
