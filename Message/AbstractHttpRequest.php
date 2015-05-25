<?php
namespace Poirot\Http\Message;

use Poirot\Http\Header\HeaderLine;
use Poirot\Http\Interfaces\Message\iHttpRequest;
use Poirot\PathUri\HttpUri;
use Poirot\PathUri\Interfaces\iHttpUri;

class AbstractHttpRequest extends AbstractHttpMessage
    implements iHttpRequest
{
    /**#@+
     * @const string METHOD constant names
     */
    const METHOD_OPTIONS  = 'OPTIONS';
    const METHOD_GET      = 'GET';
    const METHOD_HEAD     = 'HEAD';
    const METHOD_POST     = 'POST';
    const METHOD_PUT      = 'PUT';
    const METHOD_DELETE   = 'DELETE';
    const METHOD_TRACE    = 'TRACE';
    const METHOD_CONNECT  = 'CONNECT';
    const METHOD_PATCH    = 'PATCH';
    const METHOD_PROPFIND = 'PROPFIND';
    /**#@-*/

    protected $method = self::METHOD_GET;
    protected $host;
    protected $target_uri;

    /**
     * Set Request Method
     *
     * @param string $method
     *
     * @return $this
     */
    function setMethod($method)
    {
        $method = strtoupper($method);
        if (!defined('static::METHOD_' . $method))
            throw new \InvalidArgumentException('Invalid HTTP method passed');

        $this->method = $method;

        return $this;
    }

    /**
     * Get Request Method
     *
     * @return string
     */
    function getMethod()
    {
        return $this->method;
    }

    /**
     * Set Uri Target
     *
     * @param string|iHttpUri $target
     * @param bool $preserveHost When this argument is set to true,
     *                           the returned request will not update
     *                           the Host header of the returned message
     *
     * @return $this
     */
    function setTarget($target, $preserveHost = true)
    {
        if ($target === null)
            $target = '/';

        if (is_string($target))
            $target = new HttpUri($target);

        if (!$target instanceof iHttpUri)
            throw new \InvalidArgumentException(sprintf(
                'Invalid URI provided; must be null, a string, or a iHttpUri instance. "%s" given.'
                , is_object($target) ? get_class($target) : gettype($target)
            ));

        $this->target_uri = $target;

        return $this;
    }

    /**
     * Get Uri Target
     *
     * - return "/" if no one composed
     *
     * @return iHttpUri
     */
    function getTarget()
    {
        if (!$this->target_uri)
            $this->setTarget(null);

        return $this->target_uri;
    }

    /**
     * Set Host
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
        $this->host = strtolower($host);
        $this->getHeaders()->attach(new HeaderLine([
            'label'       => 'Host',
            'header_line' => $host,
        ]));

        return $this;
    }

    /**
     * Get Host
     *
     * - During construction, implementations MUST
     *   attempt to set the Host header from a provided
     *   URI if no Host header is provided.
     *
     * @throws \Exception
     * @return string
     */
    function getHost()
    {
        if (!$this->host) {
            // attempt to get host from target uri
            $host = $this->getTarget()->getHost();
            if (!$host)
                throw new \Exception('No Host Provided.');

            $this->setHost($host);
        }

        return $this->host;
    }

    /**
     * Return the formatted request line (first line) for this http request
     *
     * @return string
     */
    function renderRequestLine()
    {
        return $this->getMethod() . ' ' . $this->getTarget()->toString() . ' HTTP/' . $this->getVersion();
    }

    /**
     * Flush String Representation To Output
     *
     * @return void
     */
    function flush()
    {
        ob_end_clean();
        ob_start();
        echo $this->renderRequestLine();
        ob_end_flush();
        flush();

        parent::flush();
    }

    /**
     * Render Http Message To String
     *
     * @return string
     */
    function toString()
    {
        $return = '';
        $return .= $this->renderRequestLine();
        $return .= parent::toString();

        return $return;
    }
}
