<?php
namespace Poirot\Http\Message;

use Poirot\Core\AbstractOptions;
use Poirot\Core\DataField;
use Poirot\Core\Interfaces\iDataField;
use Poirot\Http\Headers;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHeaderCollection;
use Poirot\Http\Interfaces\Message\iHttpMessage;
use Poirot\Stream\Interfaces\iStreamable;

abstract class AbstractHttpMessage
    extends AbstractOptions
    implements iHttpMessage
{
    const VERSION_10 = '1.0';
    const VERSION_11 = '1.1';

    /**
     * @var iHeaderCollection
     */
    protected $headers;

    /**
     * @var string
     */
    protected $body;

    /**
     * @var string
     */
    protected $version = self::VERSION_11;

    /**
     * @var DataField
     */
    protected $_meta;

    /**
     * @return iDataField
     */
    function meta()
    {
        if (!$this->_meta)
            $this->_meta = new DataField;

        return $this->_meta;
    }

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
            foreach ($headers as $h)
                $this->getHeaders()->attach($h);

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
     * @param string $content
     *
     * @return $this
     */
    function setBody($content)
    {
        $this->body = $content;

        return $this;
    }

    /**
     * Get Message Body Content
     *
     * @return string
     */
    function getBody()
    {
        return $this->body;
    }

    /**
     * Render Http Message To String
     *
     * @return string
     */
    function toString()
    {
        $return = '';

        /** @var iHeader $header */
        foreach ($this->getHeaders() as $header)
            $return .= $header->toString();

        $return .= "\r\n";
        
        $body = $this->getBody();
        if ($body instanceof iStreamable) {
            while ($body->getResource()->isAlive() && !$body->getResource()->isEOF())
                $return .= $body->read(24400);
        } else {
            $return .= $body;
        }

        return $return;
    }

    /**
     * Render Http Message To String
     *
     * @return string
     */
    function __toString()
    {
        return $this->toString();
    }
}
