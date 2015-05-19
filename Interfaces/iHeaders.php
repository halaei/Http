<?php
namespace Poirot\Http\Interfaces;

/**
 * TODO Represent Headers as Collection
 */
interface iHeaders
{
    /**
     * Build Headers Object From String
     *
     * @param string $string
     *
     * @return $this
     */
    function fromString($string);

    /**
     * Represent Headers As String
     *
     * @return string
     */
    function toString();
}
