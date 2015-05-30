<?php
namespace Poirot\Http\Plugins\Request;

use Poirot\Container\Interfaces\iCService;
use Poirot\Container\Service\AbstractService;
use Poirot\Http\Interfaces\Message\iHttpMessage;
use Poirot\Http\Interfaces\Message\iHttpResponse;
use Poirot\Http\Plugins\iHttpPlugin;

class Status extends AbstractService
    implements iHttpPlugin,
    iCService
{
    /**
     * @var string Service Name
     */
    protected $name = 'Status'; // default name

    /**
     * @var iHttpResponse
     */
    protected $messageObject;

    /**
     * Does the status code indicate a client error?
     *
     * @return bool
     */
    function isClientError()
    {
        $code = $this->getMessageObject()->getStatCode();

        return ($code < 500 && $code >= 400);
    }

    /**
     * Is the request forbidden due to ACLs?
     *
     * @return bool
     */
    function isForbidden()
    {
        return (403 == $this->getMessageObject()->getStatCode());
    }

    /**
     * Is the current status "informational"?
     *
     * @return bool
     */
    function isInformational()
    {
        $code = $this->getMessageObject()->getStatCode();

        return ($code >= 100 && $code < 200);
    }

    /**
     * Does the status code indicate the resource is not found?
     *
     * @return bool
     */
    function isNotFound()
    {
        return (404 === $this->getMessageObject()->getStatCode());
    }

    /**
     * Do we have a normal, OK response?
     *
     * @return bool
     */
    function isOk()
    {
        return (200 === $this->getMessageObject()->getStatCode());
    }

    /**
     * Does the status code reflect a server error?
     *
     * @return bool
     */
    function isServerError()
    {
        $code = $this->getMessageObject()->getStatCode();

        return (500 <= $code && 600 > $code);
    }

    /**
     * Do we have a redirect?
     *
     * @return bool
     */
    function isRedirect()
    {
        $code = $this->getMessageObject()->getStatCode();

        return (300 <= $code && 400 > $code);
    }

    /**
     * Was the response successful?
     *
     * @return bool
     */
    function isSuccess()
    {
        $code = $this->getMessageObject()->getStatCode();

        return (200 <= $code && 300 > $code);
    }

    // Implement iCService:

    /**
     * Create Service
     *
     * @return mixed
     */
    function createService()
    {
        return $this;
    }

    /**
     * Set Http Message Object (Request|Response)
     *
     * note: so services can have access to http message instance
     *
     * @param iHttpMessage $httpMessage
     *
     * @return $this
     */
    function setMessageObject(iHttpMessage $httpMessage)
    {
        if (!$httpMessage instanceof iHttpResponse)
            throw new \InvalidArgumentException(sprintf(
                'This plugin need request object instance of iHttpResponse, "%s" given.'
                , get_class($httpMessage)
            ));

        $this->messageObject = $httpMessage;

        return $this;
    }

    /**
     * Get Http Message
     *
     * @return iHttpResponse
     */
    function getMessageObject()
    {
        return $this->messageObject;
    }
}
