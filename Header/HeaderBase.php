<?php
namespace Poirot\Http\Header;

use Poirot\Http\Util\UHeader;

class HeaderBase extends aHeaderHttp
{
    /**
     * Set Header Label
     *
     * @param string $label
     *
     * @return $this
     */
    function setLabel($label)
    {
        $label = (string) $label;
        if (! preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $label))
            throw new \InvalidArgumentException(sprintf(
                'Invalid header name "%s".'
                , is_null($label) ? 'null' : $label
            ));

        $this->label = $label;
        return $this;
    }

    /**
     * Build Header From Header String Representation
     *
     * @param string $line
     *
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return $this
     */
    function fromString($line)
    {
        $matches = UHeader::parseLabelValue($line);
        if ($matches === false)
            throw new \InvalidArgumentException(sprintf(
                'Invalid Header (%s).'
                , $line
            ));

        $this->setLabel(key($matches));
        // TODO
        UHeader::parseParams(current($matches));

        return $this;
    }
}
