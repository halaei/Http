<?php
namespace Poirot\Http\Interfaces;

interface iHttpResponse 
    extends iHttpMessage
{
    /**
     * Set Response Status Code
     *
     * @param int $status
     *
     * @return $this
     */
    function setStatusCode($status);

    /**
     * Get Response Status Code
     *
     * @return int
     */
    function getStatusCode();

    /**
     * Set Status Code Reason
     *
     * @param string $reason
     *
     * @return $this
     */
    function setStatusReason($reason);

    /**
     * Get Status Code Reason
     *
     * @return string
     */
    function getStatusReason();

    /**
     * Render the status line header
     *
     * @return string
     */
    function renderStatusLine();
}
