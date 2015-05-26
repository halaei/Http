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


        // ++-- target:
        $this->setTarget($_SERVER['REQUEST_URI']);

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


        $this->setHeaders($headers);

        // ++-- body:
        $bodyStream = new Streamable(
            (new WrapperClient('php://input'))->getConnect()
        );
        $this->setBody($bodyStream);
    }
}
