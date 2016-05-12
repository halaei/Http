<?php
namespace Poirot\Http\Interfaces;

use Poirot\Std\Interfaces\Struct\iDataOptions;

interface iHeader 
    extends iDataOptions
{
    /**
     * Get Header Label
     * @ignored not consider as data options
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
     * Get Field Value As String
     *
     * @return string
     */
    function renderValueLine();
    
    /**
     * Represent Header As String
     *
     * label: value_string
     *
     * @return string
     */
    function render();
}
