<?php
namespace Poirot\Http\Message\Request;

use Poirot\Http\Headers;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Stream\Streamable;
use Poirot\Stream\WrapperClient;

class PhpServerReqBuilder extends AbstractReqBuilder
{
    /**
     * Construct
     *
     * - provide option`s getter method data
     */
    final function __construct()
    {
        // ++-- method:
        if (isset($_SERVER['HTTP_METHOD']))
            $method = $_SERVER['HTTP_METHOD'];
        else
            $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;

        $this->setMethod($method);


        // ++-- target:
        $this->setUri($_SERVER['REQUEST_URI']);

        // ++-- headers:
        $headers = [];
        if (is_callable('apache_request_headers'))
            $headers = apache_request_headers();
        else
            foreach($_SERVER as $key => $val)
                if (strpos($key, 'HTTP_') === 0) {
                    $name = strtr(substr($key, 5), '_', ' ');
                    $name = strtr(ucwords(strtolower($name)), ' ', '-');

                    $headers[$name] = $val;
                }

        // ++-- host:
        if (isset($headers['Host']))
            $this->setHost($headers['Host']);

        // ++-- cookie:
        $cookie = http_build_query($_COOKIE, '', '; ');;
        $headers['Cookie'] = $cookie;

        $headers = new Headers($headers);
        $this->setHeaders($headers);

        // ++-- body:
        $body = new Streamable(
            (new WrapperClient('php://input'))->getConnect()
        );

        # multipart data
        if ($contentType = $headers->search(['label' => 'Content-Type'])) {
            /** @var iHeader $contentType */
            $contentType = $contentType->getValueString();
            if (strpos($contentType, 'multipart') !== false) {
                // it`s multipart form data
                // TODO build body data
            }
        }

        $this->setBody($body);
    }
}
