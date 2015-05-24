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
     * Represent Header As String
     *
     * @return string
     */
    function toString();
}
