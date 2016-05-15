<?php
namespace Poirot\Http\Psr;

use Poirot\Stream\Psr\StreamPsr;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

use Poirot\Http\Header\FactoryHttpHeader;
use Poirot\Http\Interfaces\iHeader;

class HttpRequest 
    extends HttpMessage
    implements RequestInterface
{
    const STREAM_CONTENT = 'php://memory';

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

    /** @var string */
    protected $uri;
    /** @var string Request Method */
    protected $method;
    /** @var string */
    protected $uriTarget;

    /** @var array Supported HTTP methods */
    protected $__validMethods = array(
        'CONNECT',
        'DELETE',
        'GET',
        'HEAD',
        'OPTIONS',
        'PATCH',
        'POST',
        'PUT',
        'TRACE',
    );

    /**
     * Construct
     *
     * - this can be used as HttpRequest Psr Driver
     *
     * @param null|string|                    $uri URI for the request, if any.
     * @param null|string                     $method HTTP method for the request, if any.
     * @param string|resource|StreamInterface $bodyStream Message body, if any.
     * @param array                           $headers Headers for the message, if any.
     *
     * @throws \InvalidArgumentException
     */
    function __construct($uri = null, $method = null, $bodyStream = null, array $headers = array())
    {
        $this->uriTarget = (string) $uri;

        # Method:
        $this->_assertValidMethod($method);
        $this->method = $method;

        # Body:
        (!$bodyStream) ?: $bodyStream = self::STREAM_CONTENT;
        $this->stream = new StreamPsr($bodyStream);

        # Headers:
        foreach($headers as $l => $v)
            $this->_getHeaders()->insert(FactoryHttpHeader::of( array($l, $v)) );
    }


    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    function getRequestTarget()
    {
        if ($this->uriTarget)
            return $this->uriTarget;
        
        $this->uriTarget = '/';
        return $this->uriTarget;
    }

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-2.7 (for the various
     *     request-target forms allowed in request messages)
     *
     * @param string|UriInterface $requestTarget
     *
     * @return self
     */
    function withRequestTarget($requestTarget)
    {
        if (!is_string($requestTarget) && ! $requestTarget instanceof UriInterface)
            throw new \InvalidArgumentException(sprintf(
                'Request Target Must Instanceof UriInterface or string. given: (%s)'
                , \Poirot\Std\flatten($requestTarget)
            ));

        $requestTarget = (string) $requestTarget;
        if ($requestTarget === $this->getRequestTarget())
            return $this;

        $new = clone $this;
        $new->uriTarget = $requestTarget;
        return $new;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string
     */
    function getMethod()
    {
        return $this->method;
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param string $method Case-sensitive method.
     * @return self
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    function withMethod($method)
    {
        if ($method === $this->getMethod())
            return $this;

        $new = clone $this;
        $new->method = $method;
        return $new;
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    function getUri()
    {
        // TODO
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return self
     */
    function withUri(UriInterface $uri, $preserveHost = false)
    {
        $new = clone $this;
        $new->uri = $uri;

        if ($preserveHost || !$uri->getHost())
            ## don't update header
            return $new;

        $host = $uri->getHost();
        ($uri->getPort() === null) ?: $host .= ':' . $uri->getPort();

        $this->_getHeaders()->insert(FactoryHttpHeader::of( array('Host', $host)) );

        return $new;
    }


    // ..

    /**
     * @Override
     *
     * {@inheritdoc}
     */
    function getHeaders()
    {
        $headers = $this->headers;
        if (! $this->hasHeader('host')
            && ($this->uri && $this->uri->getHost())
        ) {
            $host = $this->uri->getHost(). (
                ($port = $this->uri->getPort()) ? ':'.$this->uri->getPort() : ''
            );

            $this->headers->insert(FactoryHttpHeader::of( array('Host', $host)) );
        }

        $hdrArray = array();
        /** @var iHeader $h */
        foreach ($headers as $h)
            $hdrArray[$h->getLabel()] = \Poirot\Std\cast($h)->toArray();

        return $hdrArray;
    }

    /**
     * @Override
     *
     * {@inheritdoc}
     */
    function getHeader($header)
    {
        if (!$this->headers->has($header) && strtolower($header) === 'host') {
            if ($host = $this->uri->getHost()) {
                $host = $this->uri->getHost(). (
                    ($port = $this->uri->getPort()) ? ':'.$this->uri->getPort() : ''
                );

                $this->headers->insert(FactoryHttpHeader::of( array('Host', $host)) );
            }
        }

        if (!$this->headers->has($header))
            return array();

        $header = $this->headers->get($header);
        return \Poirot\Std\cast($header)->toArray();
    }

    /**
     * Assert Validate Method
     *
     * @param string $method
     *
     * @return true
     */
    private function _assertValidMethod($method)
    {
        if (null === $method)
            return true;

        $method = (string) $method;
        $method = strtoupper($method);

        if (! in_array($method, $this->__validMethods, true))
            throw new \InvalidArgumentException(sprintf(
                'Unsupported HTTP method (%s) provided',
                $method
            ));
    }
}