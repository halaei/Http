<?php
namespace Poirot\Http\Header;

use Poirot\Core\AbstractOptions;
use Poirot\Core\OpenOptions;
use Poirot\Http\Interfaces\iHeader;

abstract class AbstractHeader extends OpenOptions
    implements iHeader
{
    protected $_t_options__internal = [
        ## this method will ignore as option in prop
        'getLabel',
    ];

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
     * Get Field Value As String
     *
     * ['label'=>'Set-Cookie', 'SID'=>'31d4d96e407aad42', 'Path'=>'/', 'HttpSecure' => null]
     * Set-Cookie: SID=31d4d96e407aad42; Path="/"; HttpSecure
     *
     * @return string UHeader::filterValue
     */
    function renderValueLine()
    {
        $headerLine = [];
        foreach($this->props()->readable as $key) {
            $value = $this->__get($key);
            if (!is_scalar($value))
                // TODO
                VOID;

            if (($value!==''&&$value!==null) && !preg_match('/^\w+$/', $value)) {
                $value = preg_replace('/(["\\\\])/', "\\\\$1", $value);
                $value = "\"$value\"";
            }

            $headerLine[] = (($value) ? $key.'='.$value : $key);
        }

        return implode('; ', $headerLine);
    }
}
