<?php
namespace Poirot\Http\Message\Response;

use Poirot\Core\AbstractOptions;
use Poirot\Http\Header\HeaderFactory;
use Poirot\Http\Headers;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHeaderCollection;
use Poirot\Stream\Streamable;

class PhpServerResponseBuilder extends AbstractOptions
{
    protected $headers;

    /**
     * @return iHeaderCollection
     */
    public function getHeaders()
    {
        if (!$this->headers) {
            $this->headers = new Headers;
            $this->setHeaders(headers_list());
        }

        return $this->headers;
    }

    /**
     * @param array|iHeaderCollection $headers
     * @return $this
     */
    public function setHeaders($headers)
    {
        if (is_array($headers)) {
            foreach ($headers as $l => $h) {
                if (!$h instanceof iHeader) {
                    (is_int($l))
                        ? ### ['Header-Label: value header']
                        $h = HeaderFactory::factoryString($h)
                        : ### ['Header-Label' => 'value header']
                        $h = HeaderFactory::factory($l, $h);
                }

                $this->getHeaders()->set($h);
            }

            return $this;
        }

        if (!$headers instanceof iHeaderCollection)
            throw new \InvalidArgumentException(sprintf(
                'Headers must be instance of iHeaderCollection or array, given: "%s".'
                , \Poirot\Core\flatten($headers)
            ));

        $this->headers = $headers;
        return $this;
    }
}
