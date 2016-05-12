<?php
namespace Poirot\Http\Psr;

use Poirot\Http\Header\FactoryHttpHeader;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\Message\iHttpResponse;
use Poirot\Http\Psr\Interfaces\ResponseInterface;
use Poirot\Stream\Interfaces\iSResource;
use Poirot\Stream\Interfaces\iStreamable;
use Poirot\Stream\Psr\StreamInterface;
use Poirot\Stream\Psr\StreamPsr;

/**
 * HTTP response encapsulation.
 *
 * Responses are considered immutable; all methods that might change state are
 * implemented such that they retain the internal state of the current
 * message and return a new instance that contains the changed state.
 */
class Response extends HttpMessage
    implements ResponseInterface
{
    /** @var string */
    protected $reasonPhrase = '';
    /** @var int */
    protected $statusCode = 200;

    /**
     * Map of standard HTTP status code/reason phrases
     * @var array
     */
    protected $phrases = [
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // SERVER ERROR
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];


    /**
     * Construct
     *
     * @param string|resource|StreamInterface $body Stream identifier and/or actual stream resource
     * @param int $status Status code for the response, if any.
     * @param array $headers Headers for the response, if any.
     *
     * @throws \InvalidArgumentException on any invalid element.
     */
    function __construct($body = 'php://memory', $status = null, array $headers = [])
    {
        if ($body instanceof iHttpResponse) {
            ## http headers
            /** @var iHeader $h */
            $httpHeaders = [];
            foreach($body->getHeaders() as $h)
                $httpHeaders[$h->getLabel()] = $h->renderValueLine();
            $headers = array_merge($httpHeaders, $headers);

            ## status code
            ($status !== null) ?: $body->getStatCode();

            ## body stream
            $body = $body->getBody();
            if ($body instanceof iStreamable)
                $body = $body->getResource();
            else
                $body = 'php://memory';
        }

        if (!is_string($body) && !is_resource($body)
            && !$body instanceof StreamInterface
            && !$body instanceof iSResource
        )
            throw new \InvalidArgumentException(
                'Stream must be a string stream resource identifier, '
                . 'an actual stream resource, '
                . 'or a Psr\Http\Message\StreamInterface implementation'
            );

        ($status === null) ? $status = 200 : $this->__assertValidateStatus($status);


        $this->stream     = ($body instanceof StreamInterface) ? $body : new StreamPsr($body, 'wb+');
        $this->statusCode = (int) $status;

        # Headers:
        foreach($headers as $l => $v)
            $this->_getHeaders()->set(FactoryHttpHeader::of( array($l, $v)) );
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
        if (! $this->reasonPhrase
            && isset($this->phrases[$this->getStatusCode()])
        )
            $this->reasonPhrase = $this->phrases[$this->getStatusCode()];

        return $this->reasonPhrase;
    }

    /**
     * {@inheritdoc}
     */
    function withStatus($code, $reasonPhrase = '')
    {
        $this->__assertValidateStatus($code);

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
    protected function __assertValidateStatus($code)
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
