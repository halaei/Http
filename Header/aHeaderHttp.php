<?php
namespace Poirot\Http\Header;

use Poirot\Http\Interfaces\iHeader;
use Poirot\Std\Struct\DataOptionsOpen;

abstract class aHeaderHttp 
    extends DataOptionsOpen
    implements iHeader
{
    protected $label;

    /**
     * Get Header Label
     * @ignored
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
    abstract function importFromString($line);

    
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
     * @return string
     */
    function renderValueLine()
    {
        $headerLine = array();
        foreach($this as $key => $value) {
            if (!is_scalar($value))
                // TODO
                VOID;

            if (($value!==''&&$value!==null) && !preg_match('/^\w+$/', $value)) {
                $value = preg_replace('/(["\\\\])/', "\\\\$1", $value);
                $value = "\"$value\"";
            }

            $headerLine[] = (($value) ? $key.'='.$value : $key);
        }

        // filterValue()
        return implode('; ', $headerLine);
    }
}
