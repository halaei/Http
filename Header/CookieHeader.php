<?php
namespace Poirot\Http\Header;

use Poirot\Core\AbstractOptions;

class CookieHeader extends AbstractHeader
{

    /**
     * Build Header From Header String Representation
     *
     * @param string $line
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    function fromString($line)
    {
        // TODO: Implement fromString() method.
    }

    /**
     * Represent Header As String
     *
     * - filter values just before output
     *
     * @return string
     */
    function toString()
    {
        // TODO: Implement toString() method.
    }

    /**
     * Get Field Value As String
     *
     * @return string
     */
    function getValueString()
    {
        // TODO: Implement getValueString() method.
    }
}
