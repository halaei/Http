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
        $matches = self::parseHeader($line);
        if ($matches === false)
            ## whole set as value
            $this->setHeaderLine($line);
        else {
            $this->setLabel($matches['label']);
            $this->setHeaderLine($matches['value']);
        }

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
        $headerLine = (string) $headerLine;

        if (!$this->isValid($headerLine))
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
