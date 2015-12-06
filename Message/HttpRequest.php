<?php
namespace Poirot\Http\Message;

use Poirot\Core\Interfaces\iPoirotOptions;
use Poirot\Http\Header\HeaderFactory;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\Message\iHttpRequest;
use Poirot\Http\Psr\Interfaces\RequestInterface;
use Poirot\Http\Util;
use Poirot\PathUri\HttpUri;
use Poirot\PathUri\Interfaces\iHttpUri;

class HttpRequest extends AbstractHttpMessage
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
     * Set Options
     *
     * @param string|array|iPoirotOptions $options
     *
     * @return $this
     */
    function from($options)
    {
        if ($options instanceof RequestInterface)
            $this->fromPsr($options);
        else
            parent::from($options);

        return $this;
    }

    /**
     * Set Options From Psr Http Message Object
     *
     * @param RequestInterface $response
     *
     * @return $this
     */
    function fromPsr($response)
    {
        if (!$response instanceof RequestInterface)
            throw new \InvalidArgumentException(sprintf(
                'Request Object must instance of RequestInterface but (%s) given.'
                , \Poirot\Core\flatten($response)
            ));



        $headers = [];
        foreach($response->getHeaders() as $h => $v)
            $headers[$h] = Util::headerJoinParams($v);

        $options = [
            'method'  => $response->getMethod(),
            'uri'     => new HttpUri($response->getUri()),
            'version' => $response->getProtocolVersion(),
            'headers' => $headers,
            'body'    => $response->getBody(),
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

        // request line:
        $firstLine = array_shift($lines);
        $matches = null;
        $methods = implode('|', [
            self::METHOD_OPTIONS, self::METHOD_GET, self::METHOD_HEAD, self::METHOD_POST,
            self::METHOD_PUT, self::METHOD_DELETE, self::METHOD_TRACE, self::METHOD_CONNECT,
            self::METHOD_PATCH
        ]);
        $regex     = '#^(?P<method>' . $methods . ')\s(?P<uri>[^ ]*)(?:\sHTTP\/(?P<version>\d+\.\d+)){0,1}#';
        if (!preg_match($regex, $firstLine, $matches))
            throw new \InvalidArgumentException(
                'A valid request line was not found in the provided message.'
            );

        $this->setMethod($matches['method']);
        $this->setUri($matches['uri']);

        if (isset($matches['version']))
            $this->setVersion($matches['version']);

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
            throw new \InvalidArgumentException("Invalid HTTP method ({$method}).");

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
    function setUri($target = null, $preserveHost = true)
    {
        if ($target === null)
            $target = '/';

        if (is_string($target))
            $target = new HttpUri($target);

        if (!$target instanceof iHttpUri)
            throw new \InvalidArgumentException(sprintf(
                'Invalid URI provided; must be null, a string, or a iHttpUri instance. "%s" given.'
                , \Poirot\Core\flatten($target)
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
    function getUri()
    {
        if (!$this->target_uri)
            ## build home absolute uri if not exists
            $this->setUri();

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
            $host = $this->getUri()->getHost();
            if (!$host)
                /** @var iHeader $host */
                if ($host = $this->getHeaders()->get('Host'))
                    $host = $host->render();

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
        //TODO can implement protocol HTTP/HTTPS

        return $this->getMethod() . ' ' . $this->getUri()->toString() . ' HTTP/' . $this->getVersion();
    }

    /**
     * Flush String Representation To Output
     *
     * @param bool $withHeaders Include Headers
     *
     * @return void
     */
    function flush($withHeaders = true)
    {
        if ($withHeaders) {
            ob_start();
            echo $this->renderRequestLine();
            ob_end_flush();
            flush();
        }

        parent::flush($withHeaders);
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
        $return .= "\r\n";
        if (!$this->getHeaders()->has('Host') && $host = $this->getHost())
            $return .= 'Host: '.$host."\r\n";

        $return .= parent::toString();

        return $return;
    }
}
