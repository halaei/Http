<?php
namespace Poirot\Http\Interfaces\Respec;

use Poirot\Http\Interfaces\Message\iHttpRequest;

interface iRequestProvider
{
    /**
     * Http Request
     *
     * @return iHttpRequest
     */
    function request();
}
