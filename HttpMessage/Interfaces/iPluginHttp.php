<?php
namespace Poirot\Http\HttpMessage\Interfaces;

use Poirot\Http\Interfaces\iHttpMessage;

interface iPluginHttp
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
