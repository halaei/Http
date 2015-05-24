<?php
namespace Poirot\Http\Message;

use Poirot\Core\AbstractOptions;
use Poirot\Core\DataField;
use Poirot\Core\Entity;
use Poirot\Core\Interfaces\iDataField;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\Message\iHMessage;

abstract class AbstractHttpMessage
    extends AbstractOptions
    implements iHMessage
{
    const VERSION_10 = '1.0';
    const VERSION_11 = '1.1';

    /**
     * @var Entity
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
     * Set message metadata
     *
     * ! HTTP messages include case-insensitive header
     *   field names
     *
     * ! headers may contains multiple values, such as cookie
     *
     * @param array|iHeader $headers
     *
     * @return $this
     */
    function setHeaders($headers)
    {
        // TODO implement header
    }

    /**
     * Get Headers
     *
     * @return iHeader
     */
    function getHeaders()
    {
        // TODO implement header
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
        /*
         foreach ($this->headers()->keys() as $key)
            $return .= sprintf(
                "%s: %s\r\n",
                (string) $key,
                (string) $this->headers()->get($key)
            );
        */

        $return .= "\r\n" . $this->getBody();

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
