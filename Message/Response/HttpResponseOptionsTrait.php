<?php
namespace Poirot\Http\Message\Response;

use Poirot\Http\Message\HttpMessageOptionsTrait;
use Poirot\Http\Util\Response;

trait HttpResponseOptionsTrait
{
    use HttpMessageOptionsTrait;

    # protected $statCode;
    # protected $statReason;

    /**
     * Set Response Status Code
     *
     * @param int $status
     *
     * @return $this
     */
    function setStatCode($status)
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
    function getStatCode()
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
    function setStatReason($reason)
    {
        $this->statReason = (string) $reason;

        return $this;
    }

    /**
     * Get Status Code Reason
     *
     * @return string
     */
    function getStatReason()
    {
        if (!$this->statReason)
            ($code = $this->getStatCode() === null) ?: (
                (!$reason = Response::getStatReasonFromCode($code)) ?: (
                    $this->setStatReason($reason)
                )
            );

        return $this->statReason;
    }
}
