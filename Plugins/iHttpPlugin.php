<?php
namespace Poirot\Http\Plugins;

use Poirot\Http\Interfaces\Message\ipHttpMessage;

interface iHttpPlugin
{
    /**
     * Set Http Message Object (Request|Response)
     *
     * note: so services can have access to http message instance
     *
     * @param ipHttpMessage $httpMessage
     *
     * @return $this
     */
    function setMessageObject(ipHttpMessage $httpMessage);

    /**
     * Get Http Message
     *
     * @return ipHttpMessage
     */
    function getMessageObject();
}
