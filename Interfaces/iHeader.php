<?php
namespace Poirot\Http\Interfaces;

use Poirot\Std\Interfaces\Struct\iDataOptions;

interface iHeader 
    extends iDataOptions
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
     * label: value_string
     *
     * @return string
     */
    function render();

    /**
     * Get Field Value As String
     *
     * @return string
     */
    function renderValueLine();
}
