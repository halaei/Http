<?php
namespace Poirot\Http\Plugins\Request;

use Poirot\Http\Interfaces\Message\iHttpMessage;
use Poirot\Http\Interfaces\Message\iHttpRequest;

trait RequestPluginTrait
{
    /** @var iHttpMessage */
    protected $messageObject;


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
     * @return iHttpMessage
     */
    function getMessageObject()
    {
        return $this->messageObject;
    }
}
 