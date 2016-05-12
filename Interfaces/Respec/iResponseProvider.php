<?php
namespace Poirot\Http\Interfaces\Respec;

use Poirot\Http\Interfaces\iHttpResponse;

interface iResponseProvider
{
    /**
     * Http Response
     *
     * @return iHttpResponse
     */
    function response();
}
