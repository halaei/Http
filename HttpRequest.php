<?php
namespace Poirot\Http;

use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\Message\iHttpRequest;
use Poirot\Http\Psr\Interfaces\RequestInterface;

class HttpRequest 
    extends aHttpMessage
    implements iHttpRequest
{
    protected $method = 'GET';
    protected $host;
    protected $target_uri;
    
    
    /**
     * Parse path string to parts in associateArray
     * @param string $message
     * @return mixed
     */
    protected function doParseFromString($message)
    {
        return \Poirot\Http\parseRequestFromString($message);
    }
        
    /**
     * Set Options From Psr Http Message Object
     *
     * @param RequestInterface $psrRequest
     *
     * @return $this
     */
    protected function doParseFromPsr($psrRequest)
    {
        return \Poirot\Http\parseRequestFromPsr($psrRequest);
    }

    
    /**
     * Return the formatted request line (first line) for this http request
     *
     * - include line break at bottom
     *
     * @return string
     */
    function renderRequestLine()
    {
        //TODO can implement protocol HTTP/HTTPS
        return $this->getMethod() . ' ' . $this->getUri() . ' HTTP/' . $this->getVersion()."\r\n";
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
    function render()
    {
        $return = '';
        $return .= $this->renderRequestLine();
        $return .= parent::render();
        return $return;
    }

    /**
     * @override Append Host as Header If not exists in headers
     *
     * Render Headers
     *
     * - include line break at bottom
     *
     * @return string
     */
    function renderHeaders()
    {
        $return = parent::renderHeaders();
        if (!$this->getHeaders()->has('Host') && $host = $this->getHost())
            $return = 'Host: '.$host."\r\n" . $return;
        return $return;
    }

    
    // Options:

    /**
     * Set Request Method
     *
     * @param string $method
     *
     * @return $this
     */
    function setMethod($method)
    {
        $method = strtoupper((string) $method);
        /*if (!defined('static::METHOD_' . $method))
            throw new \InvalidArgumentException("Invalid HTTP method ({$method}).");*/

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
        $this->host = strtolower((string) $host);
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
        if ($this->host)
            return $this->host;
        
        // attempt to get host from target uri
        $host  = $this->getUri();
        $host  = parse_url($host, PHP_URL_HOST);
        if (!$host && $this->getHeaders()->has('Host')) {
            /** @var iHeader $host */
            $host = $this->getHeaders()->get('Host');
            $host = $host->render();
        }

        return $host;
    }

    /**
     * Set Uri Target
     *
     * @param string $target
     * @param bool   $preserveHost When this argument is set to true,
     *                             the returned request will not update
     *                             the Host header of the returned message
     *
     * @return $this
     */
    function setUri($target = null, $preserveHost = true)
    {
        $target = (string) $target;
        if (empty($target) && $target !== "0")
            $target = '/';
        
        // validate uri
        if (parse_url($target) === false)
            throw new \InvalidArgumentException(sprintf(
                'Malformed URI: (%s).'
                , $target
            ));
        
        $this->target_uri = (string) $target;
        return $this;
    }

    /**
     * Get Uri Target
     *
     * - return "/" if no one composed
     *
     * @return string
     */
    function getUri()
    {
        if (!$this->target_uri)
            ## build home absolute uri if not exists
            $this->setUri();

        return $this->target_uri;
    }
}
