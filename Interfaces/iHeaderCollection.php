<?php
namespace Poirot\Http\Interfaces;

interface iHeaderCollection //unknown error extends \Traversable
{
    /**
     * Set Header
     *
     * - setting the header will overwrite any
     *   previously set header value.
     *
     * ! headers label are case-insensitive
     *
     * @param iHeader $header
     *
     * @return $this
     */
    function set(iHeader $header);

    /**
     * Get Header With Label
     *
     * ! headers label are case-insensitive
     *
     * @param string $label Header Label
     *
     * @throws \Exception header not found
     * @return iHeader
     */
    function get($label);

    /**
     * Has Header With Specific Label?
     *
     * ! headers label are case-insensitive
     *
     * @param string $label
     *
     * @return bool
     */
    function has($label);
}
