<?php
namespace Poirot\Http\Header;

use Poirot\Core\AbstractOptions;
use Poirot\Core\OpenOptions;
use Poirot\Http\Interfaces\iHeader;

abstract class AbstractHeader extends OpenOptions
    implements iHeader
{
    protected $label;

    /**
     * Get Header Label
     *
     * @return string
     */
    function getLabel()
    {
        return $this->label;
    }


    /**
     * Build Header From Header String Representation
     *
     * @param string $line
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    abstract function fromString($line);

    /**
     * Set Options
     *
     * @param string|array|iHeader $options
     *
     * @return $this
     */
    function from($options)
    {
        if (is_string($options))
            $this->fromString($options);
        else
            parent::from($options);

        return $this;
    }

    /**
     * Represent Header As String
     *
     * - filter values just before output
     *
     * @return string
     */
    function render()
    {
        return $this->getLabel().': '.$this->renderValueLine();
    }

    /**
     * TODO join props and build header value
     *
     * Get Field Value As String
     *
     * - it always override by implemented classes
     *
     * @return string UHeader::filterValue
     */
    abstract function renderValueLine();
}
