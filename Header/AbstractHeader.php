<?php
namespace Poirot\Http\Header;

use Poirot\Core\AbstractOptions;
use Poirot\Core\OpenOptions;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Util;

/**
 * TODO Headers must implement properly built from array and toArray method that \
 *      represent data from within header key
 */

abstract class AbstractHeader extends OpenOptions
    implements iHeader
{
    protected $label;

    /**
     * Get Header Label
     *
     * @return string
     */
    function label()
    {
        $label = $this->label;

        if (! preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $label))
            throw new \InvalidArgumentException(sprintf(
                'Invalid header name "%s".'
                , is_null($label) ? 'null' : $label
            ));

        return $label;
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
     * Represent Header As String
     *
     * - filter values just before output
     *
     * @return string
     */
    abstract function render();


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
     * Get Field Value As String
     *
     * - it always override by implemented classes
     *
     * @return string
     */
    function renderValueLine()
    {
        $props = [];
        // TODO implement toArray
        foreach($this->props()->readable as $prop) {
            if (in_array($prop, ['header_line']))
                continue;

            $props[$prop] = $this->__get($prop);
        }

        return Util::headerFilterValue(Util::headerJoinParams($props));
    }
}
