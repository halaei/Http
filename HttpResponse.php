<?php
namespace Poirot\Http;

use Poirot\Http\Header\HeaderFactory;
use Poirot\Http\Interfaces\iHttpResponse;
use Poirot\Http\Message\Response\HttpResponseOptionsTrait;
use Poirot\Http\Plugins\HttpPluginManager;
use Poirot\Http\Plugins\HttpResponsePluginManager;
use Poirot\Http\Plugins\Response\PluginsResponseInvokable;
use Poirot\Http\Psr\Interfaces\ResponseInterface;
use Poirot\Http\Util\UHeader;
use Poirot\Http\Util\UResponse;

class HttpResponse
    extends aMessageHttp
    implements iHttpResponse
{
    use HttpResponseOptionsTrait;
    
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
                , \Poirot\Std\flatten($response)
            ));



        $headers = [];
        foreach($response->getHeaders() as $h => $v)
            $headers[$h] = UHeader::joinParams($v);

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
        $this->setBody(rtrim(implode("\r\n", $lines), "\r\n"));

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

    /**
     * Flush String Representation To Output
     *
     * @param bool $withHeaders Include Headers
     *
     * @return void
     */
    function flush($withHeaders = true)
    {
        UResponse::httpResponseCode($this->getStatCode());

        parent::flush($withHeaders);
    }

    // ...

    /**
     * @return HttpPluginManager
     */
    protected function doNewDefaultPluginManager()
    {
        return new HttpResponsePluginManager;
    }

    /**
     * @override ide completion
     * @return PluginsResponseInvokable
     */
    function plg()
    {
        if (!$this->_plugins)
            $this->_plugins = new PluginsResponseInvokable(
                $this->getPluginManager()
            );

        return $this->_plugins;
    }
}
