<?php
namespace Poirot\Http\Interfaces;

use Poirot\Core\Interfaces\iPoirotEntity;

interface iHeader extends iPoirotEntity
{
    /**
     * Represent Headers As String
     *
     * @return string
     */
    function toString();
}
