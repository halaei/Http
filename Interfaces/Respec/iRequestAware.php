<?php
namespace Poirot\Http\Interfaces\Respec;

use Poirot\Http\Interfaces\Message\iHttpRequest;

interface iRequestAware
{
    /**
     * Set Request
     *
     * @param iHttpRequest $request
     *
     * @return $this
     */
    function setRequest(iHttpRequest $request);
}
