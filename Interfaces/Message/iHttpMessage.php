<?php
namespace Poirot\Http\Interfaces\Message;

use Poirot\Core\Interfaces\iMetaProvider;
use Poirot\Http\Interfaces\iHeaderCollection;
use Poirot\Stream\Interfaces\iStreamable;

interface iHttpMessage extends iMetaProvider
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
    function setHeaders($headers);

    /**
     * Get Headers collection
     *
     * @return iHeaderCollection
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
