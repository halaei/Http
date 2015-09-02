<?php
namespace Poirot\Http\Interfaces\Respec;

use Poirot\Http\Interfaces\Message\iHttpResponse;

interface iResponseAware
{
    /**
     * Set Response
     *
     * @param iHttpResponse $response
     *
     * @return $this
     */
    function setResponse(iHttpResponse $response);
}
