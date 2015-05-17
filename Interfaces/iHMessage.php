<?php
namespace Poirot\Http\Interfaces;

use Poirot\Core\Entity;

interface iHMessage
{
    /**
     * Set message metadata
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
