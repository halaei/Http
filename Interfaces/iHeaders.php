<?php
namespace Poirot\Http\Interfaces;

/*
Origin servers SHOULD NOT fold multiple Set-Cookie header fields into
a single header field. The usual mechanism for folding HTTP headers
fields (i.e., as defined in [RFC2616]) might change the semantics of
the Set-Cookie header field because the %x2C (",") character is used
by Set-Cookie in a way that conflicts with such folding.
*/

use Poirot\Std\Interfaces\Struct\iCollection;

interface iHeaders 
    extends iCollection
{
    /**
     * Set Header
     *
     * ! headers label are case-insensitive
     *
     * @param iHeader $header
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    function insert($header);
    
    /**
     * Get Header With Label
     *
     * ! headers label are case-insensitive
     *
     * @param string $label Header Label
     *
     * @return \Traversable[iHeader]
     * @throws \Exception header not found
     */
    function get($label);

    /**
     * Delete a Header With Label Name
     *
     * @param string $label
     *
     * @return $this
     */
    function del($label);

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