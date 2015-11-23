<?php
namespace Poirot\Http\Message\Request;

use Poirot\Core\AbstractOptions;
use Poirot\Core\Interfaces\iOptionImplement;
use Poirot\Http\Headers;
use Poirot\Http\Plugins\Request\PhpServer;
use Poirot\PathUri\HttpUri;
use Poirot\Stream\Streamable;
use Poirot\Stream\WrapperClient;

class PhpServerEnv extends AbstractOptions
{
    /** @var PhpServer */
    protected $server;

    protected $host;
    protected $uri;
    protected $method;
    protected $headers;
    protected $body;
    protected $version;

    /**
     * Construct
     *
     * - build server environment upon server object
     *
     * @param PhpServer              $phpServer
     * @param array|iOptionImplement $options Options
     */
    function __construct(/*PhpServer*/ $phpServer = null, $options = null)
    {
        if ($phpServer === null)
            $phpServer = new PhpServer;

        if ($phpServer !== null && !$phpServer instanceof PhpServer)
            throw new \InvalidArgumentException(sprintf(
                'Php Server Object must instance of PhpServer. given: (%s).'
                , \Poirot\Core\flatten($phpServer)
            ));

        $this->server = $phpServer;

        parent::__construct($options);
    }

    /**
     * Set Host
     *
     * @param string $host
     *
     * @return $this
     */
    function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Get Host
     *
     * @return string
     */
    function getHost()
    {
        if ($this->host)
            return $this->host;

        $headers = $this->getHeaders();
        if ($headers->has('Host'))
            $host = $headers->get('Host')->renderValueLine();
        else
            $host = $this->getUri()->getHost();

        $this->setHost($host);
        return $this->getHost();
    }

    /**
     * Set Request Uri
     *
     * @param string|HttpUri $uri
     *
     * @return $this
     */
    function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Get Request Uri
     *
     * @return HttpUri
     */
    function getUri()
    {
        if ($this->uri)
            return $this->uri;

        $uri = new HttpUri;

        # scheme protocol
        $scheme = 'http';
        $https  = $this->server->getServer()->has('HTTPS')
            ? $this->server->getServer()->get('HTTPS') : null;
        if (($https && $https !== 'off')
            || (
                $this->getHeaders()->has('x-forwarded-proto') &&
                $this->getHeaders()->get('x-forwarded-proto') === 'https'
            )
        )
            $scheme = 'https';

        if (! empty($scheme))
            $uri->setScheme($scheme);

        # host
        $host = $this->__attainUriHost();
        if ($host) {
            $uri->setHost($host->host);
            $uri->setPort($host->port);
        }

        # uri path
        $path = $this->__attainUriPath();
        if (($pos = strpos($path, '?')) !== false)
            $path = substr($path, 0, $pos);
        $uri->setPath($path);

        # uri query
        $query = ltrim($this->server->getServer()->get('QUERY_STRING', ''), '?');
        if ($query)
            $uri->setQuery($query);

        $this->setUri($uri);
        return $this->getUri();
    }

        protected function __attainUriHost()
        {
            $Server = $this->server->getServer();

            $port = null;

            if ($this->getHeaders()->has('Host')) {
                ## Host from headers
                $host = $this->getHeaders()->get('Host')->renderValueLine();
                $port = null;

                // works for regname, IPv4 & IPv6
                if (preg_match('|\:(\d+)$|', $host, $matches)) {
                    $host = substr($host, 0, -1 * (strlen($matches[1]) + 1));
                    $port = (int) $matches[1];
                }
            } else {
                if (! $Server->has('SERVER_NAME'))
                    return null;

                $host = $Server->get('SERVER_NAME');
                if ($Server->has('SERVER_PORT'))
                    $port = (int) $Server->get('SERVER_PORT');

                if ($this->server->getServer()->has('SERVER_ADDR')
                    || preg_match('/^\[[0-9a-fA-F\:]+\]$/', $host)
                ) {
                    ## Misinterpreted IPv6-Address
                    $host = '[' . $Server->get('SERVER_ADDR') . ']';
                    $port = $port ?: 80;
                    if ($port . ']' == substr($host, strrpos($host, ':')+1))
                        // The last digit of the IPv6-Address has been taken as port
                        // Unset the port so the default port can be used
                        $port = null;
                }
            }

            return (object) ['host' => $host, 'port' => $port];
        }

        protected function __attainUriPath()
        {
            $Server = $this->server->getServer();


            // IIS7 with URL Rewrite: make sure we get the unencoded url
            // (double slash problem).
            $iisUrlRewritten = $Server->get('IIS_WasUrlRewritten', null);
            $unencodedUrl    = $Server->get('UNENCODED_URL', '');
            if ('1' == $iisUrlRewritten && ! empty($unencodedUrl))
                return $unencodedUrl;

            // ..

            $requestUri = $Server->get('REQUEST_URI');

            // Check this first so IIS will catch.
            $httpXRewriteUrl = $Server->get('HTTP_X_REWRITE_URL', null);
            if ($httpXRewriteUrl !== null)
                $requestUri = $httpXRewriteUrl;

            // Check for IIS 7.0 or later with ISAPI_Rewrite
            $httpXOriginalUrl = $Server->get('HTTP_X_ORIGINAL_URL', null);
            if ($httpXOriginalUrl !== null)
                $requestUri = $httpXOriginalUrl;


            if ($requestUri !== null)
                return preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);

            $origPathInfo = $Server->get('ORIG_PATH_INFO', '');
            if (empty($origPathInfo))
                return '/';

            return $origPathInfo;
        }

    /**
     * Set Headers
     *
     * @param array|Headers $headers
     *
     * @return $this
     */
    function setHeaders($headers)
    {
        if (is_array($headers))
            $headers = new Headers($headers);

        if (!$headers instanceof Headers)
            throw new \InvalidArgumentException(sprintf(
                'Headers must be array or instance of (Headers), given: %s.'
                , is_object($headers) ? get_class($headers) : \Poirot\Core\flatten($headers)
            ));

        $this->headers = $headers;
        return $this;
    }

    /**
     * Get Headers
     *
     * @return Headers
     */
    function getHeaders()
    {
        if ($this->headers)
            return $this->headers;

        $headers = [];

        foreach($this->server->getServer()->toArray() as $key => $val)
            if (strpos($key, 'HTTP_') === 0) {
                $name = strtr(substr($key, 5), '_', ' ');
                $name = strtr(ucwords(strtolower($name)), ' ', '-');

                $headers[$name] = $val;
            }

        // ++-- cookie:
        $cookie = http_build_query($_COOKIE, '', '; ');;
        $headers['Cookie'] = $cookie;

        $this->setHeaders($headers);
        return $this->getHeaders();
    }

    /**
     * Set Request Method
     *
     * @param $method
     *
     * @return $this
     */
    function setMethod($method)
    {
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
        if ($this->method)
            return $this->method;

        if ($this->server->getServer()->has('HTTP_METHOD'))
            $method = $this->server->getServer()->get('HTTP_METHOD');
        else
            $method = $this->server->getServer()->get('REQUEST_METHOD', null);

        $this->setMethod($method);
        return $this->getMethod();
    }

    /**
     * Set Body
     *
     * @param mixed $body
     *
     * @return $this
     */
    function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Get Body
     *
     * @return mixed
     */
    function getBody()
    {
        if ($this->body)
            return $this->body;

        $body = new Streamable(
            (new WrapperClient('php://input'))->getConnect()
        );

        # multipart data
        $headers = $this->getHeaders();

        if ($headers->has('Content-Type')) {
            $contentType = $headers->get('Content-Type');
            $contentType = $contentType->renderValueLine();
            if (strpos($contentType, 'multipart') !== false) {
                // it`s multipart form data
                // TODO build body data,
                // https://www.ietf.org/rfc/rfc2388.txt
                // http://chxo.com/be2/20050724_93bf.html

                # http://stackoverflow.com/questions/19707632/php-http-request-content-raw-data-enctype-multipart-form-data
                # http://www.chlab.ch/blog/archives/php/manually-parse-raw-http-data-php
            }
        }

        $this->setBody($body);

        return $this->getBody();
    }

    /**
     * @param mixed $version
     * @return $this
     */
    function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return mixed
     */
    function getVersion()
    {
        if ($version = $this->server->getServer()->get('SERVER_PROTOCOL', false)) {
            $isMatch = preg_match('(\d.\d+)', $version, $matches);
            if ($isMatch)
                $this->setVersion($matches[0]);
        }

        return $this->version;
    }
}
