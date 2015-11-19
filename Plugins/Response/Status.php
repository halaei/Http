<?php
namespace Poirot\Http\Plugins\Response;

use Poirot\Container\Interfaces\iCService;
use Poirot\Container\Service\AbstractService;
use Poirot\Http\Plugins\iHttpPlugin;

class Status extends AbstractService
    implements iHttpPlugin,
    iCService
{
    use ResponsePluginTrait;

    /**
     * @var string Service Name
     */
    protected $name = 'Status'; // default name

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


    // Implement iCService

    /**
     * Create Service
     *
     * @return mixed
     */
    function createService()
    {
        return $this;
    }
}
