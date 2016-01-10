<?php
namespace Poirot\Http\Header;

use Poirot\Core\AbstractOptions;
use Poirot\Http\Util\UHeader;

class HeaderLine extends AbstractHeader
{
    protected $label;

    protected $headerLine;

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
        $matches = UHeader::parseLabelValue($line);
        if ($matches === false)
            throw new \InvalidArgumentException(sprintf(
                'Invalid Header (%s).'
                , $line
            ));

        $this->setLabel(key($matches));
        $this->setHeaderLine(current($matches));

        return $this;
    }

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
     * Set Header Value String Line
     *
     * @param string $headerLine
     *
     * @return $this
     */
    function setHeaderLine($headerLine)
    {
        $headerLine = (string) $headerLine;

        if (!UHeader::isValidValue($headerLine))
            throw new \InvalidArgumentException(
                "Header value ({$headerLine}) is not valid or contains some unwanted chars."
            );

        $this->headerLine = $headerLine;
        return $this;
    }

    /**
     * Get Header Value String Line
     *
     * @return string
     */
    function getHeaderLine()
    {
        return $this->headerLine;
    }

    /**
     * Get Field Value As String
     *
     * @return string
     */
    function renderValueLine()
    {
        return UHeader::filterValue($this->getHeaderLine());
    }
}
