<?php
namespace Poirot\Http\Psr;

use Poirot\Psr7\Uri;

use Poirot\Http\Header\FactoryHttpHeader;
use Poirot\Http\Interfaces\iHttpRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class RequestBridgeInPsr
    extends aMessageBridgeInPsr
    implements RequestInterface
{
    /** @var UriInterface */
    protected $uri;

    /**
     * RequestBridgeInPsr constructor.
     * @param iHttpRequest $request
     */
    function __construct(iHttpRequest $request)
    {
        $this->httpMessage = clone $request;
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
    public function getRequestTarget()
    {
        if ($target = $this->httpMessage->getTarget())
            return $target;

        if (! $this->uri)
            return '/';

        $target = $this->uri->getPath();
        if ($this->uri->getQuery())
            $target .= '?' . $this->uri->getQuery();

        if (empty($target))
            $target = '/';

        return $target;
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
     * @param mixed $requestTarget
     * @return RequestInterface
     */
    public function withRequestTarget($requestTarget)
    {
        if (preg_match('#\s#', $requestTarget))
            throw new \InvalidArgumentException(
                'Invalid request target provided; cannot contain whitespace'
            );

        $new = clone $this;
        $new->httpMessage->setTarget($requestTarget);
        return $new;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod()
    {
        return $this->httpMessage->getMethod();
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
    public function withMethod($method)
    {
        if ($method === $this->getMethod())
            return $this;

        $new = clone $this;
        $new->httpMessage->setMethod($method);
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
    public function getUri()
    {
        if (!$this->uri)
            $this->uri = new Uri($this->httpMessage->getTarget());
        
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
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $new = clone $this;
        $new->uri = $uri;
        
        
        if ($preserveHost && $this->hasHeader('Host'))
            return $new;
        if (! $uri->getHost())
            return $new;

        
        # update host from uri
        
        $host = $uri->getHost();
        if ($uri->getPort())
            $host .= ':' . $uri->getPort();

        if ($new->hasHeader('host'))
            $new->httpMessage->headers()->del('host');

        $new->httpMessage->headers()->insert(FactoryHttpHeader::of(array('Host'=>$host)));
        return $new;
    }
    
    
    // ..
    
    function __clone()
    {
        $this->httpMessage = clone $this->httpMessage;
        ($this->uri === null) ?: $this->uri = clone $this->uri;
    }
}
