<?php
namespace Poirot\Http 
{
    use Poirot\Http\Interfaces\Message\iHttpRequest;
    use Poirot\Http\Psr\Interfaces\RequestInterface;

    /**
     * Parse Http Request Message To It's Parts
     * @param string $message
     * @return array
     */
    function parseRequestFromString($message)
    {
        if (!preg_match_all('/.*[\n]?/', $message, $lines))
            throw new \InvalidArgumentException('Error Parsing Request Message.');

        $Return = array();

        $lines = $lines[0];

        // request line:
        $firstLine = array_shift($lines);
        $matches = null;
        $methods = implode('|', array(
            iHttpRequest::METHOD_OPTIONS, iHttpRequest::METHOD_GET, iHttpRequest::METHOD_HEAD, iHttpRequest::METHOD_POST,
            iHttpRequest::METHOD_PUT, iHttpRequest::METHOD_DELETE, iHttpRequest::METHOD_TRACE, iHttpRequest::METHOD_CONNECT,
            iHttpRequest::METHOD_PATCH
        ));
        $regex     = '#^(?P<method>' . $methods . ')\s(?P<uri>[^ ]*)(?:\sHTTP\/(?P<version>\d+\.\d+)){0,1}#';
        if (!preg_match($regex, $firstLine, $matches))
            throw new \InvalidArgumentException(
                'A valid request line was not found in the provided message.'
            );

        $Return['method'] = $matches['method'];
        $Return['uri']    = $matches['uri'];

        (!isset($matches['version']))
            ?: $Return['version'] = $matches['version'];

        // headers:
        $Return['headers'] = array();
        while ($nextLine = array_shift($lines)) {
            if (trim($nextLine) == '')
                ## headers end
                break;

            $Return['headers'][] = $nextLine;
        }

        // body:
        $Return['body'] = rtrim(implode("\r\n", $lines), "\r\n");

        return $Return;
    }

    /**
     * Parse Psr Http Message To It's Parts
     * @param RequestInterface $psrRequest
     * @return array
     */
    function parseRequestFromPsr(RequestInterface $psrRequest)
    {
        if (!$psrRequest instanceof RequestInterface)
            throw new \InvalidArgumentException(sprintf(
                'Request Object must instance of RequestInterface but (%s) given.'
                , \Poirot\Std\flatten($psrRequest)
            ));


        $headers = array();
        foreach($psrRequest->getHeaders() as $h => $v)
            $headers[$h] = $v;

        $Return = array(
            'method'  => $psrRequest->getMethod(),
            'uri'     => $psrRequest->getUri(),
            'version' => $psrRequest->getProtocolVersion(),
            'headers' => $headers,
            'body'    => $psrRequest->getBody(),
        );

        return $Return;
    }
}


