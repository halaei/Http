<?php
namespace Poirot\Http\HttpMessage\Response\Plugin;

use Poirot\Http\Interfaces\iHeader;
use Psr\Http\Message\StreamInterface;


class PhpServer 
    extends aPluginResponse
{
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
     * @throws \Exception headers sent
     */
    function sendHeaders()
    {
        if ($this->isHeadersSent())
            throw new \Exception('Headers was sent.');

        
        \Poirot\Http\Response\httpResponseCode($this->getMessageObject()->getStatusCode());
        
        /** @var iHeader $header */
        foreach ($this->getMessageObject()->headers() as $header)
            header($header->render());

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

        $body = $this->getMessageObject()->getBody();
        ob_start();
        if ($body instanceof StreamInterface) {
            if ($body->isSeekable()) $body->rewind();
            while (!$body->eof())
                echo $body->read(24400);
            ob_end_flush();
            flush();
            ob_start();
        } else {
            echo $body;
        }
        ob_end_flush();
        flush();
        
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
}
