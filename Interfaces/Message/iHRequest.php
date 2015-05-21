<?php
namespace Poirot\Http\Interfaces;
use Poirot\Core\Interfaces\iMetaProvider;

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
interface iHRequest extends iHMessage, iMetaProvider
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
     * @param uri  $target
     * @param bool $preserveHost When this argument is set to true,
     *                           the returned request will not update
     *                           the Host header of the returned message
     *
     * @return $this
     */
    function setTarget($target, $preserveHost = true);

    /**
     * Get Uri Target
     *
     * - return "/" if no one composed
     *
     * @return uri
     */
    function getTarget();

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
     * @return string
     */
    function getHost();
}
