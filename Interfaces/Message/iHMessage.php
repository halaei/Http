<?php
namespace Poirot\Http\Interfaces;

use Poirot\Core\Entity;

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
     * @return Entity
     */
    function headers();

    /**
     * Set Message Body Content
     *
     * @param string $content
     *
     * @return $this
     */
    function setBody($content);

    /**
     * Get Message Body Content
     *
     * @return string
     */
    function getBody();

    /**
     * Render Http Message To String
     *
     * @return string
     */
    function toString();
}
