<?php
namespace Poirot\Http;

use Psr\Http\Message\ResponseInterface;

use Poirot\Http\Interfaces\iHttpResponse;

class HttpResponse
    extends aHttpMessage
    implements iHttpResponse
{
    protected $statCode;
    protected $statReason;

    
    /**
     * Parse path string to parts in associateArray
     *
     * !! The classes that extend this must
     *    implement parse methods
     *
     * @param string $message
     * @return mixed
     */
    protected function doParseFromString($message)
    {
        return \Poirot\Http\parseResponseFromString($message);
    }

    /**
     * Set Options From Psr Http Message Object
     *
     * @param ResponseInterface $psrResponse
     *
     * @return $this
     */
    protected function doParseFromPsr($psrResponse)
    {
        return \Poirot\Http\parseResponseFromPsr($psrResponse);
    }
    
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

    /**
     * Flush String Representation To Output
     *
     * @param bool $withHeaders Include Headers
     *
     * @return void
     */
    function flush($withHeaders = true)
    {
        \Poirot\Http\Response\httpResponseCode($this->getStatusCode());
        parent::flush($withHeaders);
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
