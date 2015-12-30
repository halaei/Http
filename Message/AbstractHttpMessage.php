<?php
namespace Poirot\Http\Message;

use Poirot\Container\Interfaces\Plugins\iInvokePluginsProvider;
use Poirot\Container\Interfaces\Plugins\iPluginManagerAware;
use Poirot\Container\Interfaces\Plugins\iPluginManagerProvider;
use Poirot\Container\Plugins\AbstractPlugins;
use Poirot\Container\Plugins\PluginsInvokable;
use Poirot\Core\AbstractOptions;
use Poirot\Core\DataField;
use Poirot\Core\Interfaces\iDataField;
use Poirot\Core\Interfaces\iPoirotOptions;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\Message\iHttpMessage;
use Poirot\Http\Plugins\HttpPluginManager;
use Poirot\Http\Psr\Interfaces\MessageInterface;
use Poirot\Http\Psr\Interfaces\RequestInterface;
use Poirot\Http\Psr\Interfaces\ResponseInterface;
use Poirot\Stream\Interfaces\iStreamable;
use Poirot\Stream\Streamable;

abstract class AbstractHttpMessage extends AbstractOptions
    implements iHttpMessage
    , iInvokePluginsProvider
    , iPluginManagerProvider
    , iPluginManagerAware
{
    const Vx1_0 = '1.0';
    const Vx1_1 = '1.1';

    /**
     * @var DataField
     */
    protected $_meta;

    /**
     * @var PluginsInvokable
     */
    protected $_plugins;

    /**
     * @var HttpPluginManager
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
     * @return PluginsInvokable
     */
    function plg()
    {
        if (!$this->_plugins)
            $this->_plugins = new PluginsInvokable(
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
     * @return HttpPluginManager
     */
    function getPluginManager()
    {
        if (!$this->pluginManager)
            $this->setPluginManager($this->_newPluginManager());

        $this->pluginManager->setMessageObject($this);

        return $this->pluginManager;
    }

    /**
     * @return HttpPluginManager
     */
    abstract protected function _newPluginManager();

    /**
     * Set Plugins Manager
     *
     * @param AbstractPlugins $plugins
     *
     * @return $this
     */
    function setPluginManager(AbstractPlugins $plugins)
    {
        if (!$plugins instanceof HttpPluginManager)
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
            if ($body->getResource()->isSeekable())
                $body->rewind();
            while ($body->getResource()->isAlive() && !$body->isEOF())
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
            foreach($this->getHeaders() as $h)
                header($h->render());
        }

        $body = $this->getBody();
        ob_start();
        if ($body instanceof iStreamable) {
            if ($body->getResource()->isSeekable())
                $body->rewind();
            while ($body->getResource()->isAlive() && !$body->isEOF())
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
