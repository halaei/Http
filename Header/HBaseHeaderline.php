<?php
namespace Poirot\Http\Header;

use Poirot\Core\AbstractOptions;

class HBaseHeaderline extends AbstractHeader
{
    protected $label;

    protected $headerLine;

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
     * Represent Header As String
     *
     * @return string
     */
    function toString()
    {
        return $this->getLabel().':'.$this->filter($this->getHeaderLine());
    }
}
