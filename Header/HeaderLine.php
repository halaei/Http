<?php
namespace Poirot\Http\Header;

use Poirot\Core\AbstractOptions;

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
        $matches = $this->parseHeader($line);

        $this->setLabel($matches['label']);
        $this->setHeaderLine($matches['value']);

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
        $this->label = (string) $label;

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
        $this->headerLine = (string) $headerLine;

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
    function getValueString()
    {
        return $this->filter($this->getHeaderLine());
    }

    /**
     * Represent Header As String
     *
     * @return string
     */
    function toString()
    {
        return $this->getLabel().':'. $this->getValueString();
    }
}
