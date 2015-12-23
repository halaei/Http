<?php
namespace Poirot\Http\Message;

use Poirot\Core\Interfaces\iPoirotOptions;
use Poirot\Http\Header\HeaderFactory;
use Poirot\Http\Interfaces\Message\iHttpResponse;
use Poirot\Http\Message\Response\HttpResponseOptionsTrait;
use Poirot\Http\Plugins\HttpPluginManager;
use Poirot\Http\Plugins\HttpRequestPluginManager;
use Poirot\Http\Plugins\HttpResponsePluginManager;
use Poirot\Http\Psr\Interfaces\ResponseInterface;
use Poirot\Http\Util\Header;

class HttpResponse extends AbstractHttpMessage
    implements iHttpResponse
{
    use HttpResponseOptionsTrait;

    /**
     * Set Options
     *
     * @param string|array|iPoirotOptions $options
     *
     * @return $this
     */
    function from($options)
    {
        if ($options instanceof ResponseInterface)
            $this->fromPsr($options);
        else
            parent::from($options);

        return $this;
    }

    /**
     * Set Options From Psr Http Message Object
     *
     * @param ResponseInterface $response
     *
     * @return $this
     */
    function fromPsr($response)
    {
        if (!$response instanceof ResponseInterface)
            throw new \InvalidArgumentException(sprintf(
                'Request Object must instance of ResponseInterface but (%s) given.'
                , \Poirot\Core\flatten($response)
            ));



        $headers = [];
        foreach($response->getHeaders() as $h => $v)
            $headers[$h] = Header::joinParams($v);

        $options = [
            'version'     => $response->getProtocolVersion(),
            'stat_code'   => $response->getStatusCode(),
            'stat_reason' => $response->getReasonPhrase(),
            'headers'     => $headers,
            'body'        => $response->getBody(),
        ];

        parent::from($options);
        return $this;
    }

    /**
     * Set Options From Http Message String
     *
     * @param string $message Message String
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    function fromString($message)
    {
        if (!preg_match_all('/.*[\r\n]?/', $message, $lines))
            throw new \InvalidArgumentException('Error Parsing Request Message.');

        $lines = $lines[0];

        $firstLine = array_shift($lines);

        $regex   = '/^HTTP\/(?P<version>1\.[01]) (?P<status>\d{3})(?:[ ]+(?P<reason>.*))?$/';
        $matches = array();
        if (!preg_match($regex, $firstLine, $matches))
            throw new \InvalidArgumentException(
                'A valid response status line was not found in the provided string.'
                . ' response:'
                . $message
            );

        $this->setVersion($matches['version']);
        $this->setStatCode($matches['status']);
        $this->setStatReason((isset($matches['reason']) ? $matches['reason'] : ''));

        // headers:
        while ($nextLine = array_shift($lines)) {
            if (trim($nextLine) == '')
                // headers end
                break;

            $this->getHeaders()->set(HeaderFactory::factoryString($nextLine));
        }

        // body:
        $this->setBody(implode("\r\n", $lines));

        return $this;
    }

    /**
     * Render the status line header
     *
     * @return string
     */
    function renderStatusLine()
    {
        $status = sprintf(
            'HTTP/%s %d %s',
            $this->getVersion(),
            $this->getStatCode(),
            $this->getStatReason()
        );

        return trim($status);
    }

    /**
     * Render Http Message To String
     *
     * @return string
     */
    function toString()
    {
        $return = '';
        $return .= $this->renderStatusLine();
        $return .= "\r\n";
        $return .= parent::toString();

        return $return;
    }


    // ...

    /**
     * @return HttpPluginManager
     */
    protected function _newPluginManager()
    {
        return new HttpResponsePluginManager;
    }
}
