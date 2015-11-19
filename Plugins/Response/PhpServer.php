<?php
namespace Poirot\Http\Plugins\Response;

use Poirot\Container\Interfaces\iCService;
use Poirot\Container\Service\AbstractService;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\Message\iHttpResponse;
use Poirot\Http\Plugins\iHttpPlugin;

class PhpServer extends AbstractService
    implements iHttpPlugin,
    iCService
{
    use ResponsePluginTrait;

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

        $this->getMessageObject()->flush(false);

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
