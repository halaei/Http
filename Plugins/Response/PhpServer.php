<?php
namespace Poirot\Http\Plugins\Response;

use Poirot\Container\Interfaces\iCService;
use Poirot\Container\Service\AbstractService;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Plugins\iHttpPlugin;

class PhpServer extends AbstractService
    implements iHttpPlugin,
    iCService ## itself can be defined as container service
{
    use ResponsePluginTrait;

    /**
     * @var string Service Name
     */
    protected $name = 'PhpServer'; // default name

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
            header($header->render());
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
