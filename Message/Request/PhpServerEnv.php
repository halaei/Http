<?php
namespace Poirot\Http\Message\Request;

use Poirot\Core\AbstractOptions;
use Poirot\Http\Headers;
use Poirot\Stream\Streamable;
use Poirot\Stream\WrapperClient;

class PhpServerEnv extends AbstractOptions
{
    protected $host;
    protected $uri;
    protected $method;
    protected $headers;
    protected $body;
    protected $version;

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
        if (!$this->host) {
            $headers = $this->getHeaders();
            $hHost   = $headers->search(['label' => 'Host']);
            if (!empty($hHost)) {
                $hHost = current($hHost);
                $this->setHost($hHost->getHeaderLine());
            }
        }

        return $this->host;
    }

    /**
     * Get Request Uri
     *
     * @return string
     */
    function getUri()
    {
        if (!$this->uri) {
            $uri = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $this->setUri($uri);
        }

        return $this->uri;
    }

    /**
     * Set Request Uri
     *
     * @param string $uri
     *
     * @return $this
     */
    function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * Get Headers
     *
     * @return Headers
     */
    function getHeaders()
    {
        if (!$this->headers)
        {
            $headers = [];

            if (is_callable('apache_request_headers')) {
                $apacheHeaders = apache_request_headers();
                if (isset($apacheHeaders['Authorization']))
                    $headers['AUTHORIZATION'] = $apacheHeaders['Authorization'];
                elseif (isset($apacheHeaders['authorization']))
                    $headers['AUTHORIZATION'] = $apacheHeaders['authorization'];
                $headers = $apacheHeaders;
            } else {
                foreach($_SERVER as $key => $val)
                    if (strpos($key, 'HTTP_') === 0) {
                        $name = strtr(substr($key, 5), '_', ' ');
                        $name = strtr(ucwords(strtolower($name)), ' ', '-');

                        $headers[$name] = $val;
                    }
            }

            // ++-- cookie:
            $cookie = http_build_query($_COOKIE, '', '; ');;
            $headers['Cookie'] = $cookie;

            $this->setHeaders($headers);
        }

        return $this->headers;
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
        if (!$this->method) {
            if (isset($_SERVER['HTTP_METHOD']))
                $method = $_SERVER['HTTP_METHOD'];
            else
                $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;

            $this->setMethod($method);
        }

        return $this->method;
    }

    /**
     * Get Body
     *
     * @return mixed
     */
    function getBody()
    {
        if (!$this->body) {
            $body = new Streamable(
                (new WrapperClient('php://input'))->getConnect()
            );

            # multipart data
            $headers = $this->getHeaders();
            $contentType = $headers->search(['label' => 'Content-Type']);
            if (is_array($contentType) && $contentType = current($contentType)) {
                $contentType = $contentType->getValueString();
                if (strpos($contentType, 'multipart') !== false) {
                    // it`s multipart form data
                    // TODO build body data,
                    // https://www.ietf.org/rfc/rfc2388.txt
                    // http://chxo.com/be2/20050724_93bf.html

                    # http://stackoverflow.com/questions/19707632/php-http-request-content-raw-data-enctype-multipart-form-data
                }
            }

            $this->setBody($body);
        }

        return $this->body;
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
     * @return mixed
     */
    function getVersion()
    {
        if (isset($_SERVER['SERVER_PROTOCOL'])) {
            $version = $_SERVER['SERVER_PROTOCOL'];
            $isMatch = preg_match('(\d.\d+)', $version, $matches);
            if ($isMatch)
                $this->setVersion($matches[0]);
        }

        return $this->version;
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


}
