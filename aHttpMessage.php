<?php
namespace Poirot\Http;

use Poirot\Std\ConfigurableSetter;
use Poirot\Std\Interfaces\Struct\iDataMean;
use Poirot\Std\Struct\aDataOptions;
use Poirot\Std\Struct\DataMean;

use Poirot\Http\Header\CollectionHeader;
use Poirot\Http\Header\FactoryHttpHeader;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHeaders;
use Poirot\Http\Interfaces\iHttpMessage;
use Psr\Http\Message\StreamInterface;


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
    /** @var string|StreamInterface */
    protected $body;

    
    
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
        if ($body instanceof StreamInterface) {
            if ($body->isSeekable()) $body->rewind();
            while (!$body->eof())
                $return .= $body->read(24400);
        } else {
            $return .= $body;
        }

        return $return;
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
        if (empty($this->version))
            $this->version = self::Vx1_1;
        
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

        if (!is_array($headers))
            throw new \InvalidArgumentException;

        foreach ($headers as $label => $h) {
            if (!$h instanceof iHeader)
                // Header-Label: value header
                $h = FactoryHttpHeader::of( array($label => $h) );

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
     * @param string|StreamInterface $content
     *
     * @return $this
     */
    function setBody($content)
    {
        if (!$content instanceof StreamInterface)
            ## Instead Of StreamInterface must convert to string
            $content = (string) $content;

        $this->body = $content;
        return $this;
    }

    /**
     * Get Message Body Content
     *
     * @return string|StreamInterface
     */
    function getBody()
    {
        return $this->body;
    }
}
