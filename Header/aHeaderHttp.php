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
     * Get Header Label
     * @ignore
     * 
     * @return string
     */
    function getLabel()
    {
        return $this->label;
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
     * ['label'=>'Set-Cookie', 'SID'=>'31d4d96e407aad42', 'Path'=>'/', 'HttpSecure' => VOID]
     * Set-Cookie: SID=31d4d96e407aad42; Path="/"; HttpSecure
     *
     * @return string
     */
    function renderValueLine()
    {
        $values = \Poirot\Std\cast($this)->toArray(null, true);
        if (empty($values))
            return '';

        $render = joinParams($values);
        return filterValue($render);
    }
}
