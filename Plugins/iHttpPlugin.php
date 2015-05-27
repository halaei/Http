<?php
namespace Poirot\Http\Plugins;

use Poirot\Http\Interfaces\Message\iHttpMessage;

interface iHttpPlugin
{
    /**
     * Set Http Message Object (Request|Response)
     *
     * note: so services can have access to http message instance
     *
     * @param iHttpMessage $httpMessage
     *
     * @return $this
     */
    function setMessageObject(iHttpMessage $httpMessage);

    /**
     * Get Http Message
     *
     * @return iHttpMessage
     */
    function getMessageObject();
}
