<?php
namespace Poirot\Http\Header;

use Poirot\Core\AbstractOptions;
use Poirot\Http\Util\Header;

/**
 * TODO Implement fromArray/toArray
 */

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
        $matches = Header::parseLabelValue($line);

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
        $this->label = (string) $label;
        return $this;
    }

    /**
     * Set Header Value String Line
     *
     * @param string|array $headerLine
     *
     * @return $this
     */
    function setHeaderLine($headerLine)
    {
        if ((is_string($headerLine)) && !Header::isValidValue($headerLine))
            throw new \InvalidArgumentException(
                "Header value ({$headerLine}) is not valid or contains some unwanted chars."
            );
        elseif (is_string($headerLine))
            $headerLine = Header::parseParams($headerLine);

        if (!is_array($headerLine))
            throw new \InvalidArgumentException(
                "Header must be valid string or array containing values."
            );

        $this->headerLine = $headerLine;
        return $this;
    }

    /**
     * Get Header Value String Line
     *
     * @return array
     */
    function getHeaderLine()
    {
        $props = [];
        foreach($this->props()->readable as $prop) {
            if (in_array($prop, ['header_line']))
                continue;

            $props[$prop] = $this->__get($prop);
        }

        return (!empty($props)) ? \Poirot\Core\array_merge($this->headerLine, [$props]) : $this->headerLine;
    }

    /**
     * Get Field Value As String
     *
     * @return string
     */
    function renderValueLine()
    {
        $params = $this->getHeaderLine();

        return Header::filterValue(Header::joinParams($params));
    }

    /**
     * Represent Header As String
     *
     * @return string
     */
    function render()
    {
        return $this->label().': '. $this->renderValueLine();
    }
}
