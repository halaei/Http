<?php
namespace Poirot\Http\Interfaces;

interface iHRequest extends iHMessage
{
    /**
     * Set Request Method
     *
     * @param string $method
     *
     * @return $this
     */
    function setMethod($method);

    /**
     * Get Request Method
     *
     * @return string
     */
    function getMethod();

    function setTarget($target);

    function getTarget();

    /**
     * Set Host
     *
     * @param string $host
     *
     * @return $this
     */
    function setHost($host);

    /**
     * Get Host
     *
     * @return string
     */
    function getHost();
}
