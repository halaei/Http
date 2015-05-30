<?php
namespace Poirot\Http\Plugins\Response;

use Poirot\Container\Interfaces\iCService;
use Poirot\Container\Service\AbstractService;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\Message\iHttpMessage;
use Poirot\Http\Interfaces\Message\iHttpResponse;
use Poirot\Http\Plugins\iHttpPlugin;

class PhpServer extends AbstractService
    implements iHttpPlugin,
    iCService
{
    /**
     * @var string Service Name
     */
    protected $name = 'PhpServer'; // default name

    /**
     * @var iHttpResponse
     */
    protected $messageObject;

    protected $isHeadersSent;
    protected $isContentSent;

    /**
     * @return bool
     */
    function isHeadersSent()
    {
        return headers_sent() || $this->isHeadersSent;
    }

    /**
     * @return bool
     */
    function isContentSent()
    {
        return $this->isContentSent;
    }

    /**
     * Send HTTP headers
     *
     * @return $this
     */
    function sendHeaders()
    {
        if ($this->isHeadersSent())
            return $this;

        $status  = $this->getMessageObject()->renderStatusLine();
        header($status);

        /** @var iHeader $header */
        foreach ($this->getMessageObject()->getHeaders() as $header) {
            header($header->toString());
        }

        $this->isHeadersSent = true;

        return $this;
    }

    /**
     * Send content
     *
     * @return $this
     */
    function sendContent()
    {
        if ($this->isContentSent())
            return $this;

        $this->getMessageObject()->flush();

        $this->isContentSent = true;

        return $this;
    }

    /**
     * Send HTTP response
     *
     * @return $this
     */
    function send()
    {
        $this
            ->sendHeaders()
            ->sendContent()
        ;

        return $this;
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
