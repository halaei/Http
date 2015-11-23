<?php
namespace Poirot\Http\Interfaces\Message;

use Poirot\PathUri\Interfaces\iHttpUri;

/**
 * Provides the general representation of an HTTP request message.
 * However, server-side requests need additional treatment, due to
 * the nature of the server-side environment.
 *
 * PHP has provided simplification around input marshaling via superglobals such as:
 * $_COOKIE, $_GET, $_POST, $_FILES, $_SERVER
 *
 * @see iHMRServer
 *
 */
interface iHttpRequest extends iHttpMessage
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

    /**
     * Set Uri Target
     *
     * @param string|iHttpUri  $target
     * @param bool $preserveHost When this argument is set to true,
     *                           the returned request will not update
     *                           the Host header of the returned message
     *
     * @return $this
     */
    function setUri($target = null, $preserveHost = true);

    /**
     * Get Uri Target
     *
     * - return "/" if no one composed
     *
     * @return iHttpUri
     */
    function getUri();

    /**
     * Set Host
     *
     * - During construction, implementations MUST
     *   attempt to set the Host header from a provided
     *   URI if no Host header is provided.
     *
     * note: Host header typically mirrors the host component of the URI,
     *       However, the HTTP specification allows the Host header to
     *       differ from each of the two.
     *
     * @param string $host
     *
     * @return $this
     */
    function setHost($host);

    /**
     * Get Host
     *
     * - During construction, implementations MUST
     *   attempt to set the Host header from a provided
     *   URI if no Host header is provided.
     *
     * @return string
     */
    function getHost();

    /**
     * Return the formatted request line (first line) for this http request
     *
     * @return string
     */
    function renderRequestLine();
}
