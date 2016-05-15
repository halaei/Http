<?php
namespace Poirot\Http\Psr;

use Psr\Http\Message\ResponseInterface;

use Poirot\Http\Header\FactoryHttpHeader;
use Poirot\Stream\Psr\StreamPsr;
use Psr\Http\Message\StreamInterface;

/**
 * HTTP response encapsulation.
 *
 * Responses are considered immutable; all methods that might change state are
 * implemented such that they retain the internal state of the current
 * message and return a new instance that contains the changed state.
 */
class Response 
    extends HttpMessage
    implements ResponseInterface
{
    /** @var string */
    protected $reasonPhrase = '';
    /** @var int */
    protected $statusCode = 200;


    /**
     * Construct
     *
     * @param string|resource|StreamInterface $body    Stream identifier and/or actual stream resource
     * @param int                             $status  Status code for the response, if any.
     * @param array                           $headers Headers for the response, if any.
     *
     * @throws \InvalidArgumentException on any invalid element.
     */
    function __construct($body = 'php://memory', $status = null, array $headers = array())
    {
        if (!is_string($body) && !is_resource($body)
            && !$body instanceof StreamInterface
        )
            throw new \InvalidArgumentException(
                'Stream must be a string stream resource identifier, '
                . 'an actual stream resource, '
                . 'or a Psr\Http\Message\StreamInterface implementation'
            );

        ($status === null) ? $status = 200 : $this->_assertValidateStatus($status);
        
        $this->stream     = ($body instanceof StreamInterface) ? $body : new StreamPsr($body, 'wb+');
        $this->statusCode = (int) $status;

        # Headers:
        foreach($headers as $l => $v)
            $this->_getHeaders()->insert( FactoryHttpHeader::of(array($l, $v)) );
    }

    /**
     * {@inheritdoc}
     */
    function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * {@inheritdoc}
     */
    function getReasonPhrase()
    {
        if (!$this->reasonPhrase)
            $this->reasonPhrase = \Poirot\Http\Response\getStatReasonFromCode($this->getStatusCode());

        return $this->reasonPhrase;
    }

    /**
     * {@inheritdoc}
     */
    function withStatus($code, $reasonPhrase = '')
    {
        $this->_assertValidateStatus($code);

        $new = clone $this;
        $new->statusCode   = (int) $code;
        $new->reasonPhrase = $reasonPhrase;
        return $new;
    }

    /**
     * Validate a status code.
     *
     * @param int|string $code
     * @throws \InvalidArgumentException on an invalid status code.
     */
    protected function _assertValidateStatus($code)
    {
        if (! is_numeric($code)
            || is_float($code)
            || $code < 100
            || $code >= 600
        )
            throw new \InvalidArgumentException(sprintf(
                'Invalid status code "%s"; must be an integer between 100 and 599, inclusive',
                (is_scalar($code) ? $code : gettype($code))
            ));
    }
}
