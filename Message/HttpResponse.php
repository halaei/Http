<?php
namespace Poirot\Http\Message;

use Poirot\Core\Interfaces\iPoirotOptions;
use Poirot\Http\Header\HeaderFactory;
use Poirot\Http\Interfaces\Message\iHttpResponse;
use Poirot\Http\Psr\Interfaces\ResponseInterface;
use Poirot\Http\Util;

class HttpResponse extends AbstractHttpMessage
    implements iHttpResponse
{
    protected $statCode;
    protected $statReason;

    /**
     * Map of standard HTTP status code/reason phrases
     *
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
     * Set Options
     *
     * @param string|array|iPoirotOptions $options
     *
     * @return $this
     */
    function from($options)
    {
        if ($options instanceof ResponseInterface)
            $this->fromPsr($options);
        else
            parent::from($options);

        return $this;
    }

    /**
     * Set Options From Psr Http Message Object
     *
     * @param ResponseInterface $response
     *
     * @return $this
     */
    function fromPsr($response)
    {
        if (!$response instanceof ResponseInterface)
            throw new \InvalidArgumentException(sprintf(
                'Request Object must instance of ResponseInterface but (%s) given.'
                , \Poirot\Core\flatten($response)
            ));



        $headers = [];
        foreach($response->getHeaders() as $h => $v)
            $headers[$h] = Util::headerJoinParams($v);

        $options = [
            'version'     => $response->getProtocolVersion(),
            'stat_code'   => $response->getStatusCode(),
            'stat_reason' => $response->getReasonPhrase(),
            'headers'     => $headers,
            'body'        => $response->getBody(),
        ];

        parent::from($options);
        return $this;
    }

    /**
     * Set Options From Http Message String
     *
     * @param string $message Message String
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    function fromString($message)
    {
        if (!preg_match_all('/.*[\n]?/', $message, $lines))
            throw new \InvalidArgumentException('Error Parsing Request Message.');

        $lines = $lines[0];

        $firstLine = array_shift($lines);

        $regex   = '/^HTTP\/(?P<version>1\.[01]) (?P<status>\d{3})(?:[ ]+(?P<reason>.*))?$/';
        $matches = array();
        if (!preg_match($regex, $firstLine, $matches))
            throw new \InvalidArgumentException(
                'A valid response status line was not found in the provided string'
            );

        $this->setVersion($matches['version']);
        $this->setStatCode($matches['status']);
        $this->setStatReason((isset($matches['reason']) ? $matches['reason'] : ''));

        // headers:
        while ($nextLine = array_shift($lines)) {
            if (trim($nextLine) == '')
                // headers end
                break;

            $this->getHeaders()->set(HeaderFactory::factoryString($nextLine));
        }

        // body:
        $this->setBody(implode("\r\n", $lines));

        return $this;
    }

    /**
     * Set Response Status Code
     *
     * @param int $status
     *
     * @return $this
     */
    function setStatCode($status)
    {
        if (! is_numeric($status)
            || is_float($status)
            || $status < 100
            || $status >= 600
        )
            throw new \InvalidArgumentException(sprintf(
                'Invalid status code "%s"; must be an integer between 100 and 599, inclusive',
                (is_scalar($status) ? $status : gettype($status))
            ));

        $this->statCode = $status;

        return $this;
    }

    /**
     * Get Response Status Code
     *
     * @return int
     */
    function getStatCode()
    {
        return $this->statCode;
    }

    /**
     * Set Status Code Reason
     *
     * @param string $reason
     *
     * @return $this
     */
    function setStatReason($reason)
    {
        $this->statReason = (string) $reason;

        return $this;
    }

    /**
     * Get Status Code Reason
     *
     * @return string
     */
    function getStatReason()
    {
        if (!$this->statReason)
            ($code = $this->getStatCode() === null) ?: (
                (!isset($this->phrases[$code])) ?: (
                    $this->setStatReason($this->phrases[$code])
                )
            );

        return $this->statReason;
    }

    /**
     * Render the status line header
     *
     * @return string
     */
    function renderStatusLine()
    {
        $status = sprintf(
            'HTTP/%s %d %s',
            $this->getVersion(),
            $this->getStatCode(),
            $this->getStatReason()
        );

        return trim($status);
    }

    /**
     * Render Http Message To String
     *
     * @return string
     */
    function toString()
    {
        $return = '';
        $return .= $this->renderStatusLine();
        $return .= "\r\n";
        $return .= parent::toString();

        return $return;
    }
}
