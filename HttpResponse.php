<?php
namespace Poirot\Http;

use Poirot\Http\Interfaces\iHttpResponse;

class HttpResponse
    extends aHttpMessage
    implements iHttpResponse
{
    protected $statCode;
    protected $statReason;

    
    /**
     * Render the status line header
     *
     * @return string
     */
    function renderStatusLine()
    {
        $status = sprintf(
            'HTTP/%s %d %s',
            $this->getVersion(),
            $this->getStatusCode(),
            $this->getStatusReason()
        );

        return trim($status);
    }

    /**
     * Render Http Message To String
     *
     * @return string
     */
    function render()
    {
        $return = '';
        $return .= $this->renderStatusLine();
        $return .= "\r\n";
        $return .= parent::render();

        return $return;
    }
    
    
    // Options:
    
    /**
     * Set Response Status Code
     *
     * @param int $status
     *
     * @return $this
     */
    function setStatusCode($status)
    {
        if (! is_numeric($status)
            || is_float($status)
            || $status < 100
            || $status >= 600
        )
            throw new \InvalidArgumentException(sprintf(
                'Invalid status code "%s"; must be an integer between 100 and 599, inclusive',
                (is_scalar($status) ? $status : gettype($status))
            ));

        $this->statCode = $status;
        return $this;
    }

    /**
     * Get Response Status Code
     *
     * @return int
     */
    function getStatusCode()
    {
        return (int) $this->statCode;
    }

    /**
     * Set Status Code Reason
     *
     * @param string $reason
     *
     * @return $this
     */
    function setStatusReason($reason)
    {
        $this->statReason = (string) $reason;
        return $this;
    }

    /**
     * Get Status Code Reason
     *
     * @return string
     */
    function getStatusReason()
    {
        if ($this->statReason)
            return $this->statReason;

        ($reason = \Poirot\Http\Response\getStatReasonFromCode($this->getStatusCode()))
            ?: $reason = 'Unknown';

        return $reason;
    }
}
