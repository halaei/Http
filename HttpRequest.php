<?php
namespace Poirot\Http;

use Poirot\Http\Interfaces\Message\iHttpRequest;
use Poirot\Http\Plugins\HttpPluginManager;
use Poirot\Http\Plugins\HttpRequestPluginManager;
use Poirot\Http\Plugins\Request\PluginsRequestInvokable;
use Poirot\Http\Psr\Interfaces\RequestInterface;

class HttpRequest 
    extends aMessageHttp
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
        $return .= parent::toString();
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
        $method = strtoupper($method);
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
                if ($this->getHeaders()->has('Host') && $host = $this->getHeaders()->get('Host'))
                    $host = $host->render();

            $this->setHost($host);
        }

        return $this->host;
    }

    /**
     * Set Uri Target
     *
     * @param string|iHttpUri|iSeqPathUri $target
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
        elseif($target instanceof iSeqPathUri)
            $target = (new HttpUri)->setPath($target);

        if (!$target instanceof iHttpUri)
            throw new \InvalidArgumentException(sprintf(
                'Invalid URI provided; must be null, a string, or a iHttpUri, iSeqPathUri instance. "%s" given.'
                , \Poirot\Std\flatten($target)
            ));

        $this->target_uri = $target;
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

    /**
     * Set Uri Options
     *
     * @param iHttpUri|iDataStruct|array $options
     *
     * @return $this
     */
    function setUriOptions($options)
    {
        if ($options instanceof iDataStruct)
            $options = $options->toArray();

        if(is_array($options))
            $this->getUri()->fromArray($options);
        elseif ($options instanceof iHttpUri)
            $this->getUri()->fromPathUri($options);
        else
            throw new \InvalidArgumentException;

        return $this;
    }
    

    // ...

    /**
     * @return HttpPluginManager
     */
    protected function doNewDefaultPluginManager()
    {
        return new HttpRequestPluginManager;
    }

    /**
     * @override ide completion
     * @return PluginsRequestInvokable
     */
    function plg()
    {
        if (!$this->_plugins)
            $this->_plugins = new PluginsRequestInvokable(
                $this->getPluginManager()
            );

        return $this->_plugins;
    }
}
