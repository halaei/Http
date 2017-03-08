<?php
namespace Poirot\Http\Interfaces;

use Poirot\Std\Interfaces\Struct\iDataOptions;

interface iHeader 
    extends iDataOptions
{
    /**
     * Set Header Label
     *
     * @param string $label
     *
     * @return $this
     */
    function setLabel($label);
    
    /**
     * Get Header Label
     * @ignored not consider as data options
     * 
     * @return string
     */
    function getLabel();
    
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
