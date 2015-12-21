<?php
namespace Poirot\Http\Message;

use Poirot\Http\Header\HeaderFactory;
use Poirot\Http\Headers;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHeaderCollection;
use Poirot\Stream\Interfaces\iStreamable;
use Poirot\Stream\Psr\StreamInterface;
use Poirot\Stream\SResource;
use Poirot\Stream\Streamable;

trait HttpMessageOptionsTrait
{
    # protected $version;
    # protected $headers;
    # protected $body;


    /**
     * Set Version
     *
     * @param string $ver
     *
     * @return $this
     */
    function setVersion($ver)
    {
        $this->version = (string) $ver;

        return $this;
    }

    /**
     * Get Version
     *
     * @return string
     */
    function getVersion()
    {
        return $this->version;
    }

    /**
     * Set message headers or headers collection
     *
     * ! HTTP messages include case-insensitive header
     *   field names
     *
     * ! headers may contains multiple values, such as cookie
     *
     * @param array|iHeaderCollection $headers
     *
     * @return $this
     */
    function setHeaders($headers)
    {
        if ($headers instanceof iHeaderCollection)
            $this->headers = $headers;

        if (is_array($headers))
            foreach ($headers as $label => $h) {
                if (!$h instanceof iHeader)
                    // Header-Label: value header
                    $h = HeaderFactory::factory($label, $h);

                $this->getHeaders()->set($h);
            }

        return $this;
    }

    /**
     * Get Headers collection
     *
     * @return iHeaderCollection
     */
    function getHeaders()
    {
        if (!$this->headers)
            $this->headers = new Headers();

        return $this->headers;
    }

    /**
     * Set Message Body Content
     *
     * !! if StreamInterface given it must contain
     *    'resource' => resource key of meta key data
     *
     * @param string|iStreamable|StreamInterface $content
     *
     * @return $this
     */
    function setBody($content)
    {
        if ($content instanceof StreamInterface)
            // TODO wrap seekable stream for none-seekable streams
            $content = new Streamable(new SResource($content));

        $this->body = $content;

        return $this;
    }

    /**
     * Get Message Body Content
     *
     * @return string|iStreamable
     */
    function getBody()
    {
        return $this->body;
    }
}
