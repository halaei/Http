<?php
namespace Poirot\Http\Message;

use Poirot\Core\Interfaces\iPoirotOptions;
use Poirot\Http\Header\HeaderFactory;
use Poirot\Http\Interfaces\iHeaderCollection;
use Poirot\Http\Interfaces\Message\iHttpMessage;
use Poirot\Http\Interfaces\Message\iHttpRequest;
use Poirot\Http\Message\Request\HttpRequestOptionsTrait;
use Poirot\Http\Plugins\HttpPluginManager;
use Poirot\Http\Plugins\HttpRequestPluginManager;
use Poirot\Http\Plugins\Request\PluginsRequestInvokable;
use Poirot\Http\Psr\Interfaces\RequestInterface;
use Poirot\Http\Util\UHeader;
use Poirot\PathUri\HttpUri;
use Poirot\PathUri\Interfaces\iHttpUri;
use Poirot\PathUri\Interfaces\iSeqPathUri;
use Poirot\Stream\Interfaces\iStreamable;
use Psr\Http\Message\StreamInterface;

class HttpRequest extends AbstractHttpMessage
    implements iHttpRequest
{
    use HttpRequestOptionsTrait;

    /**#@+
     * @const string METHOD constant names
     */
    const METHOD_OPTIONS  = 'OPTIONS';
    const METHOD_GET      = 'GET';
    const METHOD_HEAD     = 'HEAD';
    const METHOD_POST     = 'POST';
    const METHOD_PUT      = 'PUT';
    const METHOD_DELETE   = 'DELETE';
    const METHOD_TRACE    = 'TRACE';
    const METHOD_CONNECT  = 'CONNECT';
    const METHOD_PATCH    = 'PATCH';
    const METHOD_PROPFIND = 'PROPFIND';
    /**#@-*/

    /**
     * Set Options
     *
     * @param string|array|iPoirotOptions $options
     *
     * @return $this
     */
    function from($options)
    {
        if ($options instanceof RequestInterface)
            $this->fromPsr($options);
        else
            parent::from($options);

        return $this;
    }

    /**
     * Set Options From Psr Http Message Object
     *
     * @param RequestInterface $response
     *
     * @return $this
     */
    function fromPsr($response)
    {
        if (!$response instanceof RequestInterface)
            throw new \InvalidArgumentException(sprintf(
                'Request Object must instance of RequestInterface but (%s) given.'
                , \Poirot\Core\flatten($response)
            ));



        $headers = [];
        foreach($response->getHeaders() as $h => $v)
            $headers[$h] = UHeader::joinParams($v);

        $options = [
            'method'  => $response->getMethod(),
            'uri'     => new HttpUri($response->getUri()),
            'version' => $response->getProtocolVersion(),
            'headers' => $headers,
            'body'    => $response->getBody(),
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
         if (!preg_match_all('/.*[\n]?/', $message, $lines))
             throw new \InvalidArgumentException('Error Parsing Request Message.');

        $lines = $lines[0];

        // request line:
        $firstLine = array_shift($lines);
        $matches = null;
        $methods = implode('|', [
            self::METHOD_OPTIONS, self::METHOD_GET, self::METHOD_HEAD, self::METHOD_POST,
            self::METHOD_PUT, self::METHOD_DELETE, self::METHOD_TRACE, self::METHOD_CONNECT,
            self::METHOD_PATCH
        ]);
        $regex     = '#^(?P<method>' . $methods . ')\s(?P<uri>[^ ]*)(?:\sHTTP\/(?P<version>\d+\.\d+)){0,1}#';
        if (!preg_match($regex, $firstLine, $matches))
            throw new \InvalidArgumentException(
                'A valid request line was not found in the provided message.'
            );

        $this->setMethod($matches['method']);
        $this->setUri($matches['uri']);

        if (isset($matches['version']))
            $this->setVersion($matches['version']);

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
     * Return the formatted request line (first line) for this http request
     *
     * - include line break at bottom
     *
     * @return string
     */
    function renderRequestLine()
    {
        //TODO can implement protocol HTTP/HTTPS

        return $this->getMethod() . ' ' . $this->getUri()->toString() . ' HTTP/' . $this->getVersion()."\r\n";
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
            ob_start();
            echo $this->renderRequestLine();
            ob_end_flush();
            flush();
        }

        parent::flush($withHeaders);
    }

    /**
     * Render Http Message To String
     *
     * @return string
     */
    function toString()
    {
        $return = '';
        $return .= $this->renderRequestLine();
        $return .= parent::toString();

        return $return;
    }

    /**
     * @override Append Host as Header If not exists in headers
     *
     * Render Headers
     *
     * - include line break at bottom
     *
     * @return string
     */
    function renderHeaders()
    {
        $return = parent::renderHeaders();
        if (!$this->getHeaders()->has('Host') && $host = $this->getHost())
            $return = 'Host: '.$host."\r\n" . $return;

        return $return;
    }


    // ...

    /**
     * @return HttpPluginManager
     */
    protected function _newPluginManager()
    {
        return new HttpRequestPluginManager;
    }

    /**
     * @override ide completion
     * @return PluginsRequestInvokable
     */
    function plg()
    {
        if (!$this->_plugins)
            $this->_plugins = new PluginsRequestInvokable(
                $this->getPluginManager()
            );

        return $this->_plugins;
    }
}
