<?php
namespace Poirot\Http\Psr;

use Poirot\Http\Header\HeaderFactory;
use Poirot\Http\Headers;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Psr\Interfaces\MessageInterface;
use Poirot\Http\Header;
use Poirot\Stream\Psr\StreamInterface;

class HttpMessage implements MessageInterface
{
    const Vx1_0 = '1.0';
    const Vx1_1 = '1.1';

    /** @var string */
    protected $version = self::Vx1_1;
    /** @var Headers */
    protected $headers;
    /** @var StreamInterface */
    protected $stream;

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    function getProtocolVersion()
    {
        return $this->version;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new protocol version.
     *
     * @param string $version HTTP protocol version
     * @return HttpMessage
     */
    function withProtocolVersion($version)
    {
        $self = new \ReflectionClass(HttpMessage::class);

        if (!in_array($version, $self->getConstants()))
            throw new \Exception("Protocol {$version} not supported.");

        $self = $this;
        if ($version !== $this->getProtocolVersion())
            $self = clone $self;

        $self->version = $version;
        return $self;
    }

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return array Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    function getHeaders()
    {
        $headers = [];
        /** @var iHeader $h */
        foreach ($this->__getHeaders() as $h)
            $headers[$h->label()] = Header::parseParams($h->renderValueLine());

        return $headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    function hasHeader($name)
    {
        return $this->__getHeaders()->has($name);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * If the header does not appear in the message, this method MUST return an
     * empty array.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method MUST
     *    return an empty array.
     */
    function getHeader($name)
    {
        if (!$this->__getHeaders()->has($name))
            return [];

        $header = $this->__getHeaders()->get($name);

        return Header::parseParams($header->renderValueLine());
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    function getHeaderLine($name)
    {
        if (!$this->headers->has($name))
            return '';

        return $this->__getHeaders()->get($name)->renderValueLine();
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    function withHeader($name, $value)
    {
        $header = HeaderFactory::factory($name, $value);

        $new = clone $this;
        $new->__getHeaders()->set($header);

        return $new;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new header and/or value.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    function withAddedHeader($name, $value)
    {
        (!is_string($value)) ?: $value = [$value];

        if (! is_array($value))
            throw new \InvalidArgumentException(sprintf(
                'Invalid header value; must be a string or array. given: (%s).'
                , \Poirot\Core\flatten($value)
            ));

        if (!$this->__getHeaders()->has($name))
            return $this->withHeader($name, $value);


        // ..

        /** @var iHeader $header */
        $header = clone $this->getHeader($name);

        $new = clone $this;
        foreach($value as $p => $b)
            if (is_int($p))
                // ['en_US', ..]
                $header->__set($b, null);
            else
                // ['foo' => 'bar']
                $header->__set($p, $b);

        $new->__getHeaders()->set($header);

        return $new;
    }

    /**
     * Return an instance without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the named header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return self
     */
    function withoutHeader($name)
    {
        if (!$this->headers->has($name))
            return $this;

        $new     = clone $this;
        $headers = $new->__getHeaders()->del($name);
        $new->headers = $headers;

        return $new;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    function getBody()
    {
        return $this->stream;
    }

    /**
     * Return an instance with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamInterface $body Body.
     * @return self
     * @throws \InvalidArgumentException When the body is not valid.
     */
    function withBody(StreamInterface $body)
    {
        $new = clone $this;
        $new->stream = $body;

        return $new;
    }

    // ...

    protected function __getHeaders()
    {
        if (!$this->headers)
            $this->headers = new Headers;

        return $this->headers;
    }


    function __clone()
    {
        $this->headers = clone $this->headers;
    }
}
