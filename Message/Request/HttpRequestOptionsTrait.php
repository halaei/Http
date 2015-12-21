<?php
namespace Poirot\Http\Message\Request;

use Poirot\Core\Interfaces\iDataSetConveyor;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Message\HttpMessageOptionsTrait;
use Poirot\PathUri\HttpUri;
use Poirot\PathUri\Interfaces\iHttpUri;
use Poirot\PathUri\Interfaces\iSeqPathUri;

trait HttpRequestOptionsTrait
{
    use HttpMessageOptionsTrait;

    protected $method = 'GET';
    protected $host;
    protected $target_uri;

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
     * Set Uri Options
     *
     * @param iHttpUri|iDataSetConveyor|array $options
     *
     * @return $this
     */
    function setUriOptions($options)
    {
        if ($options instanceof iDataSetConveyor)
            $options = $options->toArray();

        if(is_array($options))
            $this->getUri()->fromArray($options);
        elseif ($options instanceof iHttpUri)
            $this->getUri()->fromPathUri($options);
        else
            throw new \InvalidArgumentException;

        return $this;
    }
}
