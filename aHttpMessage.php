<?php
namespace Poirot\Http;

use Poirot\Std\ConfigurableSetter;
use Poirot\Std\Interfaces\Struct\iDataMean;
use Poirot\Std\Struct\aDataOptions;
use Poirot\Std\Struct\DataMean;

use Poirot\Stream\Interfaces\iStreamable;
use Poirot\Stream\Psr\StreamInterface as PsrStreamInterface;
use Poirot\Stream\Streamable;

use Poirot\Http\Header\CollectionHeader;
use Poirot\Http\Header\FactoryHttpHeader;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHeaders;
use Poirot\Http\Interfaces\iHttpMessage;
use Poirot\Http\Psr\Interfaces\MessageInterface;


abstract class aHttpMessage
    extends ConfigurableSetter
    implements iHttpMessage
{
    const Vx1_0 = '1.0';
    const Vx1_1 = '1.1';

    /** @var DataMean */
    protected $meta;

    protected $version = '1.1';
    /** @var iHeaders */
    protected $headers;
    /** @var string|iStreamable */
    protected $body;

    
    // Implement Configurable:
    
    /**
     * Parse path string to parts in associateArray
     * 
     * !! The classes that extend this abstract must 
     *    implement parse methods
     * 
     * @param string $message
     * @return mixed
     */
    abstract protected function doParseFromString($message);

    /**
     * Set Options From Psr Http Message Object
     *
     * @param MessageInterface $psrMessage
     *
     * @return $this
     */
    abstract protected function doParseFromPsr($psrMessage);
    
    /**
     * @override Parse String and Psr Message
     * @inheritdoc
     */
    static function parseWith($optionsResource, array $_ = null)
    {
        if (!static::isConfigurableWith($optionsResource))
            throw new \InvalidArgumentException(sprintf(
                'Invalid Resource provided; given: (%s).'
                , \Poirot\Std\flatten($optionsResource)
            ));


        $self = new static;
        if (is_string($optionsResource))
            $optionsResource = $self->doParseFromString($optionsResource);

        if ($optionsResource instanceof MessageInterface)
            $optionsResource = $self->doParseFromPsr($optionsResource);
        
        return $optionsResource;
    }

    /**
     * @override Parse String and Psr Message
     * @inheritdoc
     */
    static function isConfigurableWith($optionsResource)
    {
        return $optionsResource instanceof MessageInterface
        || is_array($optionsResource) || is_string($optionsResource);
    }
    
    // -

    /**
     * @return iDataMean
     */
    function meta()
    {
        if (!$this->meta)
            $this->meta = new DataMean();

        return $this->meta;
    }

    /**
     * Render Headers
     *
     * - include line break at bottom
     *
     * @return string
     */
    function renderHeaders()
    {
        $return = '';
        /** @var iHeader $header */
        foreach ($this->getHeaders() as $header)
            $return .= trim($header->render())."\r\n";
        $return .= "\r\n";

        return $return;
    }

    /**
     * Render Http Message To String
     *
     * - render header
     * - render body
     *
     * @return string
     */
    function render()
    {
        $return = $this->renderHeaders();

        $body = $this->getBody();
        if ($body instanceof PsrStreamInterface) {
            if ($body->isSeekable()) $body->rewind();
            while (!$body->eof())
                $return .= $body->read(24400);
        } else {
            $return .= $body;
        }

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
        if ($withHeaders) {
            /** @var iHeader $h */
            foreach($this->getHeaders() as $h)
                header($h->render());
        }

        $body = $this->getBody();
        ob_start();
        if ($body instanceof PsrStreamInterface) {
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
    }


    // Options:

    /**
     * Set Version
     *
     * @param string $ver
     *
     * @return $this
     */
    function setVersion($ver)
    {
        $this->version = (string) $ver;
        return $this;
    }

    /**
     * Get Version
     *
     * @return string
     */
    function getVersion()
    {
        return $this->version;
    }

    /**
     * Set message headers or headers collection
     *
     * ! HTTP messages include case-insensitive header
     *   field names
     *
     * ! headers may contains multiple values, such as cookie
     *
     * @param array|iHeaders $headers
     *
     * @return $this
     */
    function setHeaders($headers)
    {
        if ($headers instanceof iHeaders) {
            $tHeaders = array();
            foreach($headers as $h)
                $tHeaders[] = $h;
            $headers = $tHeaders;
        }

        if (is_array($headers))
            foreach ($headers as $label => $h) {
                if (!$h instanceof iHeader)
                    // Header-Label: value header
                    $h = FactoryHttpHeader::of( array($label, $h) );

                $this->getHeaders()->insert($h);
            }

        return $this;
    }

    /**
     * Get Headers collection
     *
     * @return iHeaders
     */
    function getHeaders()
    {
        if (!$this->headers)
            $this->headers = new CollectionHeader();

        return $this->headers;
    }

    /**
     * Set Message Body Content
     *
     * @param string|PsrStreamInterface $content
     *
     * @return $this
     */
    function setBody($content)
    {
        if (!$content instanceof PsrStreamInterface)
            ## Instead Of StreamInterface must convert to string
            $content = (string) $content;

        $this->body = $content;
        return $this;
    }

    /**
     * Get Message Body Content
     *
     * @return string|PsrStreamInterface
     */
    function getBody()
    {
        return $this->body;
    }
}
