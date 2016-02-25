<?php
namespace Poirot\Http\Interfaces\Message;

interface iHttpResponse extends ipHttpMessage
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

    /**
     * Render the status line header
     *
     * @return string
     */
    function renderStatusLine();
}
