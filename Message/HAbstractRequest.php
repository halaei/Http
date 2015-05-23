<?php
namespace Poirot\Http\Message;

use Poirot\Http\Interfaces\Message\iHRequest;
use Poirot\PathUri\Interfaces\iHttpUri;
use Poirot\PathUri\Interfaces\iSeqPathUri;

class HAbstractRequest extends AbstractHttpMessage
    implements iHRequest
{
    /**
     * Set Request Method
     *
     * @param string $method
     *
     * @return $this
     */
    function setMethod($method)
    {
        // TODO: Implement setMethod() method.
    }

    /**
     * Get Request Method
     *
     * @return string
     */
    function getMethod()
    {
        // TODO: Implement getMethod() method.
    }

    /**
     * Set Uri Target
     *
     * @param iSeqPathUri|iHttpUri $target
     * @param bool $preserveHost When this argument is set to true,
     *                           the returned request will not update
     *                           the Host header of the returned message
     *
     * @return $this
     */
    function setTarget($target, $preserveHost = true)
    {
        // TODO: Implement setTarget() method.
    }

    /**
     * Get Uri Target
     *
     * - return "/" if no one composed
     *
     * @return iSeqPathUri|iHttpUri
     */
    function getTarget()
    {
        // TODO: Implement getTarget() method.
    }

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
    function setHost($host)
    {
        // TODO: Implement setHost() method.
    }

    /**
     * Get Host
     *
     * @return string
     */
    function getHost()
    {
        // TODO: Implement getHost() method.
    }
}
