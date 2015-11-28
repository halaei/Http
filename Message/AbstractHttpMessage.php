<?php
namespace Poirot\Http\Message;

use OAuth2\RequestInterface;
use Poirot\Container\Interfaces\Plugins\iInvokePluginsProvider;
use Poirot\Container\Interfaces\Plugins\iPluginManagerAware;
use Poirot\Container\Interfaces\Plugins\iPluginManagerProvider;
use Poirot\Container\Plugins\AbstractPlugins;
use Poirot\Container\Plugins\InvokablePlugins;
use Poirot\Core\AbstractOptions;
use Poirot\Core\DataField;
use Poirot\Core\Interfaces\iDataField;
use Poirot\Core\Interfaces\iPoirotOptions;
use Poirot\Http\Header\HeaderFactory;
use Poirot\Http\Headers;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHeaderCollection;
use Poirot\Http\Interfaces\Message\iHttpMessage;
use Poirot\Http\Plugins\HttpPluginsManager;
use Poirot\Http\Psr\Interfaces\MessageInterface;
use Poirot\Stream\Interfaces\iStreamable;
use Poirot\Stream\Psr\StreamInterface;
use Poirot\Stream\SResource;
use Poirot\Stream\Streamable;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractHttpMessage extends AbstractOptions
    implements iHttpMessage
    , iInvokePluginsProvider
    , iPluginManagerProvider
    , iPluginManagerAware
{
    const Vx1_0 = '1.0';
    const Vx1_1 = '1.1';

    /**
     * @var iHeaderCollection
     */
    protected $headers;

    /**
     * @var string
     */
    protected $body;

    /**
     * @var string
     */
    protected $version = self::Vx1_1;

    /**
     * @var DataField
     */
    protected $_meta;

    /**
     * @var InvokablePlugins
     */
    protected $_plugins;

    /**
     * @var HttpPluginsManager
     */
    protected $pluginManager;


    // Implement Options:

    /**
     * Set Options
     *
     * @param string|array|iPoirotOptions $options
     *
     * @return $this
     */
    function from($options)
    {
        if (is_string($options))
            $this->fromString($options);
        else
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
    abstract function fromString($message);

    /**
     * Set Options From Psr Http Message Object
     *
     * @param RequestInterface|ResponseInterface|MessageInterface $PsrMessage
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    abstract function fromPsr($PsrMessage);


    // Implement Plugins Manager:

    /**
     * Plugin Manager
     *
     * @return InvokablePlugins
     */
    function plugin()
    {
        if (!$this->_plugins)
            $this->_plugins = new InvokablePlugins(
                $this->getPluginManager()
            );

        return $this->_plugins;
    }

    /**
     * Get Plugins Manager
     *
     * note: it's recommended that create http message as
     *       factory service on application level and build
     *       pluginManager with required config builder and
     *       keep it clear on this state
     *
     *
     * @return HttpPluginsManager
     */
    function getPluginManager()
    {
        if (!$this->pluginManager)
            $this->setPluginManager(new HttpPluginsManager);

        $this->pluginManager->setMessageObject($this);

        return $this->pluginManager;
    }

    /**
     * Set Plugins Manager
     *
     * @param AbstractPlugins $plugins
     *
     * @return $this
     */
    function setPluginManager(AbstractPlugins $plugins)
    {
        if (!$plugins instanceof HttpPluginsManager)
            throw new \InvalidArgumentException(sprintf(
                'Plugins Manager must instance of (HttpPluginsManager) given (%s).'
                , get_class($plugins)
            ));

        $this->pluginManager = $plugins;

        return $this;
    }

    // Implement Http Message Features:

    /**
     * @return iDataField
     */
    function meta()
    {
        if (!$this->_meta)
            $this->_meta = new DataField;

        return $this->_meta;
    }

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
     * @param array|iHeaderCollection $headers
     *
     * @return $this
     */
    function setHeaders($headers)
    {
        if ($headers instanceof iHeaderCollection)
            $this->headers = $headers;

        if (is_array($headers))
            foreach ($headers as $label => $h) {
                if (!$h instanceof iHeader)
                    // Header-Label: value header
                    $h = HeaderFactory::factory($label, $h);

                $this->getHeaders()->set($h);
            }

        return $this;
    }

    /**
     * Get Headers collection
     *
     * @return iHeaderCollection
     */
    function getHeaders()
    {
        if (!$this->headers)
            $this->headers = new Headers();

        return $this->headers;
    }

    /**
     * Set Message Body Content
     *
     * !! if StreamInterface given it must contain
     *    'resource' => resource key of meta key data
     *
     * @param string|iStreamable|StreamInterface $content
     *
     * @return $this
     */
    function setBody($content)
    {
        if ($content instanceof StreamInterface)
            $content = new Streamable(new SResource($content->getMetadata('resource')));

        $this->body = $content;

        return $this;
    }

    /**
     * Get Message Body Content
     *
     * @return string|iStreamable
     */
    function getBody()
    {
        return $this->body;
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
    function toString()
    {
        $return = $this->renderHeaders();

        $body = $this->getBody();
        if ($body instanceof iStreamable) {
            while ($body->getResource()->isAlive() && !$body->getResource()->isEOF())
                $return .= $body->read(24400);
        } else {
            $return .= $body;
        }

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
        if ($withHeaders) {
            ob_start();
            echo $this->renderHeaders();
            ob_end_flush();
            flush();
        }

        $body = $this->getBody();
        ob_start();
        if ($body instanceof iStreamable) {
            while ($body->getResource()->isAlive() && !$body->getResource()->isEOF())
                echo $body->read(24400);
                ob_end_flush();
                flush();
                ob_start();
        } else {
            echo $body;
        }
        ob_end_flush();
        flush();
    }
}
