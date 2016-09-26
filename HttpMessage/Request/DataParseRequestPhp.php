<?php
namespace Poirot\Http\HttpMessage\Request;

use Poirot\Http\HttpMessage\Request\Plugin\PhpServer;
use Poirot\Std\Struct\aDataOptions;

use Poirot\Stream\Psr\StreamBridgeInPsr;
use Poirot\Stream\Streamable;


class DataParseRequestPhp
    extends aDataOptions
{
    /** @var PhpServer */
    protected $server;

    protected $host;
    protected $uri;
    protected $headers;
    protected $body;
    protected $version;

    
    /**
     * @override This is readonly option
     * @inheritdoc
     */
    function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Get Request Method
     * @see HttpRequest::setMethod
     * 
     * @return string
     */
    function getMethod()
    {
        $method = 'GET';
        if (isset($_SERVER['HTTP_METHOD']))
            $method = $_SERVER['HTTP_METHOD'];
        elseif (isset($_SERVER['REQUEST_METHOD']))
            $method = $_SERVER['REQUEST_METHOD'];

        return $method;
    }
    
    /**
     * Get Host
     * @see HttpRequest::setHost
     * 
     * @return string
     */
    function getHost()
    {
        $host = null;
        if (isset($_SERVER['HTTP_HOST']))
            ## from request headers
            $host = $_SERVER['HTTP_HOST'];
        elseif (isset($_SERVER['SERVER_NAME']))
            $host = $_SERVER['SERVER_NAME']. (
                    ( isset($_SERVER['SERVER_PORT']) ) ? ':'.$_SERVER['SERVER_PORT'] : ''
                );
        elseif (isset($_SERVER['SERVER_ADDR']))
            $host = $_SERVER['SERVER_ADDR']. (
                    ( isset($_SERVER['SERVER_ADDR']) ) ? ':'.$_SERVER['SERVER_PORT'] : ''
                );

        if (preg_match('/^\[[0-9a-fA-F\:]+\]$/', $host))
            ## Misinterpreted IPv6-Address
            $host = '[' . $host . ']';

        return $host;
    }

    /**
     * @see HttpRequest::setVersion
     * @return mixed
     */
    function getVersion()
    {
        $version = null;
        if (isset($_SERVER['SERVER_PROTOCOL'])) {
            $isMatch = preg_match('(\d.\d+)', $_SERVER['SERVER_PROTOCOL'], $matches);
            (!$isMatch) ?: $version = $matches[0];
        }

        return $version;
    }

    /**
     * Get Request Uri
     * @see HttpRequest::setTarget
     * 
     * @return string
     */
    function getTarget()
    {
        // IIS7 with URL Rewrite: make sure we get the unencoded url
        // (double slash problem).
        $iisUrlRewritten = (isset($_SERVER['IIS_WasUrlRewritten'])) ? $_SERVER['IIS_WasUrlRewritten'] : null;
        $unencodedUrl    = (isset($_SERVER['UNENCODED_URL']))       ? $_SERVER['UNENCODED_URL']       : null;
        if ('1' == $iisUrlRewritten && $unencodedUrl)
            return $unencodedUrl;

        // ..

        $requestUri = $_SERVER['REQUEST_URI'];

        // Check this first so IIS will catch.
        $httpXRewriteUrl = (isset($_SERVER['HTTP_X_REWRITE_URL'])) ? $_SERVER['HTTP_X_REWRITE_URL'] : null;
        if ($httpXRewriteUrl !== null)
            $requestUri = $httpXRewriteUrl;

        // Check for IIS 7.0 or later with ISAPI_Rewrite
        $httpXOriginalUrl = (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) ? $_SERVER['HTTP_X_ORIGINAL_URL'] : null;
        if ($httpXOriginalUrl !== null)
            $requestUri = $httpXOriginalUrl;

        if ($requestUri !== null)
            return preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);


        $origPathInfo = (isset($_SERVER['ORIG_PATH_INFO'])) ? $_SERVER['ORIG_PATH_INFO'] : '';
        if (empty($origPathInfo))
            return '/';

        return $origPathInfo;
    }

    /**
     * Get Headers
     * @see HttpRequest::setHeaders
     * 
     * @return array
     */
    function getHeaders()
    {
        $headers = array();
        foreach($_SERVER as $key => $val) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = strtr(substr($key, 5), '_', ' ');
                $name = strtr(ucwords(strtolower($name)), ' ', '-');
                ## host header represent separately on request object
                // if ($name === 'Host') continue;

                $headers[$name] = $val;
            } elseif(in_array($key, array('CONTENT_TYPE', 'CONTENT_LENGTH'))) {
                ## specific headers that not always present
                $name = strtr($key, '_', ' ');
                $name = strtr(ucwords(strtolower($name)), ' ', '-');
                $headers[$name] = $val;
            }
        }

        // ++-- Authorization
        if (isset($_SERVER['PHP_AUTH_PW']) && isset($_SERVER['PHP_AUTH_USER'])) {
            if (!isset($headers['Authorization'])) {
                /*
                 * note: either can use .htaccess configuration
                 * 
                 * ## FIX Missing Authorization Request Header
                 * RewriteCond %{HTTP:Authorization} ^(.*)
                 * RewriteRule .* - [e=HTTP_AUTHORIZATION:%1] 
                 */
                if (function_exists(apache_request_headers())) {
                    $apacheHeaders = apache_request_headers();
                    if (isset($apacheHeaders['Authorization']))
                        $headers['Authorization'] = $apacheHeaders['Authorization'];
                }
            }
        }

        // ++-- Cookie
        $cookie = http_build_query($_COOKIE, '', '; ');
        (empty($cookie)) ?: $headers['Cookie'] = $cookie;

        ksort($headers);
        return $headers;
    }

    /**
     * Get Body
     * @see HttpRequest::setBody
     * 
     * @return mixed
     */
    function getBody()
    {
        $headers = $this->getHeaders();

        if (
            $this->getMethod() == 'POST'
            && isset($headers['Content-Type'])
            && strpos($headers['Content-Type'], 'multipart') !== false
        ) {
            // it`s multipart POST form data
            ## input raw body not represent in php when method is POST
            #- it can be as sending files or send form data in multipart

            $boundary = $headers['Content-Type'];
            preg_match('/boundary=(?P<boundary>.*)/', $boundary, $matches);
            $boundary = $matches['boundary'];

            $rawData = array_merge($_FILES, $_POST);
            $stream  = new StreamBodyMultiPart($rawData, $boundary);
        } else {
            // TODO it can be implemented with Buffer Stream
            $stream = new Streamable\STemporary('php://temp', 'r+');
            $stream->write(file_get_contents('php://input'));
        }

        $stream->rewind();
        $stream = new StreamBridgeInPsr($stream);
        return $stream;
    }
}
