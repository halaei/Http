<?php
namespace Poirot\Http\HttpMessage\Plugins\Response;

use Poirot\Http\HttpMessage\Response\Plugin\aPluginResponse;

class Status 
    extends aPluginResponse
{
    /**
     * Does the status code indicate a client error?
     *
     * @return bool
     */
    function isClientError()
    {
        $code = $this->getMessageObject()->getStatusCode();

        return ($code < 500 && $code >= 400);
    }

    /**
     * Is the request forbidden due to ACLs?
     *
     * @return bool
     */
    function isForbidden()
    {
        return (403 == $this->getMessageObject()->getStatusCode());
    }

    /**
     * Is the current status "informational"?
     *
     * @return bool
     */
    function isInformational()
    {
        $code = $this->getMessageObject()->getStatusCode();

        return ($code >= 100 && $code < 200);
    }

    /**
     * Does the status code indicate the resource is not found?
     *
     * @return bool
     */
    function isNotFound()
    {
        return (404 === $this->getMessageObject()->getStatusCode());
    }

    /**
     * Do we have a normal, OK response?
     *
     * @return bool
     */
    function isOk()
    {
        return (200 === $this->getMessageObject()->getStatusCode());
    }

    /**
     * Does the status code reflect a server error?
     *
     * @return bool
     */
    function isServerError()
    {
        $code = $this->getMessageObject()->getStatusCode();

        return (500 <= $code && 600 > $code);
    }

    /**
     * Do we have a redirect?
     *
     * @return bool
     */
    function isRedirect()
    {
        $code = $this->getMessageObject()->getStatusCode();

        return (300 <= $code && 400 > $code);
    }

    /**
     * Was the response successful?
     *
     * @return bool
     */
    function isSuccess()
    {
        $code = $this->getMessageObject()->getStatusCode();
        return (200 <= $code && $code < 300);
    }
}
