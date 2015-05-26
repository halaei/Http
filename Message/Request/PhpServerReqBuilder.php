<?php
namespace Poirot\Http\Message\Request;

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


        // ++-- headers:
        foreach($_SERVER as $key => $val)
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace(['HTTP_', '_'], ['', '-'], $key);
                if (strtolower($name) == 'host')
                    // ++-- host:
                    $this->setHost($val);

                $headers[$name] = $val;
            }

        $this->setHeaders($headers);


        // ++-- body:
        $bodyStream = new Streamable(
            (new WrapperClient('php://input'))->getConnect()
        );
        $this->setBody($bodyStream);
    }
}
