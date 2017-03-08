<?php
namespace Poirot\Http\Header;

class HeaderLine 
    extends aHeaderHttp
{
    protected $valueLine;
    
    
    /**
     * Set Header Value Line
     *
     * - given header value line will parse and import as data set
     *
     * @param string $headerValue
     *
     * @return $this
     */
    function setValueLine($headerValue)
    {
        $this->valueLine = (string) $headerValue;
        return $this;
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
        if ($this->valueLine)
            return $this->valueLine;
        
        return $this->valueLine = parent::renderValueLine();
    }
}
