<?php
namespace Poirot\Http\Message\Response;

use Poirot\Http\Header\factoryHttpHeader;
use Poirot\Http\CollectionHeader;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHeaders;
use Poirot\Std\Struct\AbstractOptionsData;
use Poirot\Stream\Streamable;

class BuilderPhpServerResponse extends AbstractOptionsData
{
    protected $headers;

    /**
     * @return iHeaders
     */
    public function getHeaders()
    {
        if (!$this->headers) {
            $this->headers = new CollectionHeader;
            $this->setHeaders(headers_list());
        }

        return $this->headers;
    }

    /**
     * @param array|iHeaders $headers
     * @return $this
     */
    public function setHeaders($headers)
    {
        if (is_array($headers)) {
            foreach ($headers as $l => $h) {
                if (!$h instanceof iHeader) {
                    (is_int($l))
                        ? ### ['Header-Label: value header']
                        $h = factoryHttpHeader::factoryString($h)
                        : ### ['Header-Label' => 'value header']
                        $h = factoryHttpHeader::of($l, $h);
                }

                $this->getHeaders()->set($h);
            }

            return $this;
        }

        if (!$headers instanceof iHeaders)
            throw new \InvalidArgumentException(sprintf(
                'Headers must be instance of iHeaderCollection or array, given: "%s".'
                , \Poirot\Std\flatten($headers)
            ));

        $this->headers = $headers;
        return $this;
    }
}
