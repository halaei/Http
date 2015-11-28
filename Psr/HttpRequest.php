<?php
namespace Poirot\Http\Psr;

use Poirot\Http\Header\HeaderFactory;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\Message\iHttpRequest;
use Poirot\Http\Psr\Interfaces\RequestInterface;
use Poirot\PathUri\Psr\HttpUri;
use Poirot\PathUri\Psr\UriInterface;
use Poirot\Stream\Psr\PsrStream;
use Poirot\Stream\Psr\StreamInterface;

class HttpRequest extends HttpMessage
    implements RequestInterface
{
    const STREAM_CONTENT = 'php://memory';

    /** @var UriInterface */
    protected $uri;
    /** @var string Request Method */
    protected $method;
    /** @var string */
    protected $uriTarget;

    /** @var array Supported HTTP methods */
    protected $__validMethods = [
        'CONNECT',
        'DELETE',
        'GET',
        'HEAD',
        'OPTIONS',
        'PATCH',
        'POST',
        'PUT',
        'TRACE',
    ];

    /**
     * Construct
     *
     * - this can be used as HttpRequest Psr Driver
     *
     * @param null|string|iHttpRequest        $uri URI for the request, if any.
     * @param null|string                     $method HTTP method for the request, if any.
     * @param string|resource|StreamInterface $bodyStream Message body, if any.
     * @param array                           $headers Headers for the message, if any.
     *
     * @throws \InvalidArgumentException
     */
    function __construct($uri = null, $method = null, $bodyStream = null, array $headers = [])
    {
        if ($uri instanceof iHttpRequest) {
            ## prepare arguments

            ## request method
            ($method !== null) ?: $method = $uri->getMethod();

            ## http headers
            /** @var iHeader $h */
            $httpHeaders = [];
            foreach($uri->getHeaders() as $h)
                $httpHeaders[$h->label()] = $h->renderValueLine();
            $headers = array_merge($httpHeaders, $headers);

            ## body stream
            ($bodyStream !== null) ?: $bodyStream = $uri->getBody();

            ## request target uri
            $uri = new HttpUri($uri->getUri());
        }

        // ..

        # Uri:
        if (is_string($uri))
            $uri = new HttpUri($uri);

        if ($uri !== null && ! $uri instanceof UriInterface)
            throw new \InvalidArgumentException(
                'Invalid URI provided; must be null, a string, or a Psr\Http\Message\UriInterface instance'
            );

        $this->uri = $uri;

        # Method:
        $this->__assertValidMethod($method);
        $this->method = $method;

        # Body:
        (!$bodyStream) ?: $bodyStream = self::STREAM_CONTENT;
        $this->stream = new PsrStream($bodyStream);

        # Headers:
        foreach($headers as $l => $v)
            $this->__getHeaders()->set(HeaderFactory::factory($l, $v));

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

        $uri = $this->uri->__toString();
        $this->uriTarget = ($uri) ? $uri : '/';

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
     * @param string|HttpUri $requestTarget
     *
     * @return self
     */
    function withRequestTarget($requestTarget)
    {
        if (!is_string($requestTarget) && ! $requestTarget instanceof UriInterface)
            throw new \InvalidArgumentException(sprintf(
                'Request Target Must Instanceof UriInterface or string. given: (%s)'
                , \Poirot\Core\flatten($requestTarget)
            ));

        if (is_string($requestTarget))
            ## build and validate path again
            $requestTarget = new HttpUri($requestTarget);

        $requestTarget = $requestTarget->__toString();

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
        return $this->uri;
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

        $this->__getHeaders()->set(HeaderFactory::factory('Host', $host));

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

            $this->headers->set(HeaderFactory::factory('Host', $host));
        }

        $hdrArray = [];
        /** @var iHeader $h */
        foreach ($headers as $h)
            $hdrArray[$h->label()] = $h->toArray();

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

                $this->headers->set(HeaderFactory::factory('Host', $host));
            }
        }

        if (!$this->headers->has($header))
            return [];

        $header = $this->headers->get($header);
        return $header->toArray();
    }

    /**
     * Assert Validate Method
     *
     * @param string $method
     *
     * @return true
     */
    private function __assertValidMethod($method)
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