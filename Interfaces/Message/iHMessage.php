<?php
namespace Poirot\Http\Interfaces;

use Poirot\Core\Entity;
use Poirot\Core\Interfaces\iPoirotEntity;
use Poirot\Stream\Interfaces\iStreamable;

interface iHMessage
{
    /**
     * Set Version
     *
     * @param string $version
     *
     * @return $this
     */
    function setVersion($version);

    /**
     * Get Version
     *
     * @return string
     */
    function getVersion();

    /**
     * Set message metadata
     *
     * ! HTTP messages include case-insensitive header
     *   field names
     *
     * ! headers may contains multiple values, such as cookie
     *
     * @param array|iHeaders $headers
     *
     * @return $this
     */
    function setHeaders($headers);

    /**
     * Get Headers
     *
     * @return iHeaders
     */
    function getHeaders();

    /**
     * Set Message Body Content
     *
     * @param string|iStreamable $content
     *
     * @return $this
     */
    function setBody($content);

    /**
     * Get Message Body Content
     *
     * @return string|iStreamable
     */
    function getBody();

    /**
     * Render Http Message To String
     *
     * @return string
     */
    function toString();
}
