<?php
namespace Poirot\Http\Interfaces;

use Poirot\Core\Interfaces\iPoirotOptions;

interface iHeader extends iPoirotOptions
{
    /**
     * Get Header Label
     *
     * @return string
     */
    function getLabel();

    /**
     * Build Header From Header String Representation
     *
     * @param string $line
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    function fromString($line);
    
    /**
     * Represent Header As String
     *
     * @return string
     */
    function toString();
}
