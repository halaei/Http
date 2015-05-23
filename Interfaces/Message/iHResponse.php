<?php
namespace Poirot\Http\Interfaces\Message;

interface iHResponse extends iHMessage
{
    /**
     * Set Response Status Code
     *
     * @param int $status
     *
     * @return $this
     */
    function setStatCode($status);

    /**
     * Get Response Status Code
     *
     * @return int
     */
    function getStatCode();

    /**
     * Set Status Code Reason
     *
     * @param string $reason
     *
     * @return $this
     */
    function setStatReason($reason);

    /**
     * Get Status Code Reason
     *
     * @return string
     */
    function getStatReason();
}
