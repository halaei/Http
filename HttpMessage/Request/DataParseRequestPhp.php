<?php
namespace Poirot\Http\HttpMessage\Request;

use Poirot\Http\HttpMessage\Request\Plugin\PhpServer;
use Poirot\Http\Message\Request\StreamBodyMultiPart;
use Poirot\Std\Struct\aDataOptions;

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
     * Get Request Method
     *
     * @return string
     */
    function getMethod()
    {
        $method = 'GET';
        if (isset($_SERVER['HTTP_METHOD']))
            $method = $_SERVER['HTTP_METHOD'];
        elseif (isset($_SERVER['HTTP_METHOD']))
            $method = $_SERVER['REQUEST_METHOD'];

        return $method;
    }
    
    /**
     * Get Host
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
     *
     * @return string
     */
    function getUri()
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
     *
     * @return array
     */
    function getHeaders()
    {
        $headers = array();
        foreach($_SERVER as $key => $val)
            if (strpos($key, 'HTTP_') === 0) {
                $name = strtr(substr($key, 5), '_', ' ');
                $name = strtr(ucwords(strtolower($name)), ' ', '-');
                ## host header represent separately on request object
                if ($name === 'Host') continue;

                $headers[$name] = $val;
            }

        // ++-- cookie:
        $cookie = http_build_query($_COOKIE, '', '; ');
        (empty($cookie)) ?: $headers['Cookie'] = $cookie;

        return $headers;
    }

    /**
     * Get Body
     *
     * @return mixed
     */
    function getBody()
    {
        $_f__readInputStream = function() {

        };


        // ..

        # multipart data
        $headers = $this->getHeaders();
        if (isset($headers['Content-Type'])
            && strpos($headers['Content-Type'], 'multipart') !== false
        ) {
            // it`s multipart form data
            if ($this->getMethod() == 'POST')
                ## create MultiPart Stream From _FILES
                $rawData =  \Poirot\Http\Psr\normalizeFiles($_FILES);

            // TODO
            return (new StreamBodyMultiPart($rawData));
        }


    }
}
