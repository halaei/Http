<?php
namespace Poirot\Http\Message;

use Poirot\Core\AbstractOptions;
use Poirot\Core\Entity;
use Poirot\Core\Interfaces\EntityInterface;
use Poirot\Core\Interfaces\iPoirotEntity;
use Poirot\Http\Interfaces\iHMessage;

abstract class AbstractHttpMessage
    extends AbstractOptions
    implements iHMessage
{
    const VERSION_1p0 = '1.0';
    const VERSION_1p1 = '1.1';

    /**
     * @var Entity
     */
    protected $headers;

    /**
     * @var string
     */
    protected $body;

    /**
     * @var string
     */
    protected $version = self::VERSION_1p1;

    /**
     * Set message metadata
     *
     * @return Entity
     */
    function headers()
    {
        if (!$this->headers)
            // TODO Headers From String
            $this->setHeaders(new Entity());

        return $this->headers;
    }

    /**
     * Set Meta Data
     *
     * @param array|EntityInterface $meta
     *
     * @throws \Exception
     * @return $this
     */
    function setHeaders($meta)
    {
        if ($meta instanceof EntityInterface) {
            $this->headers = $meta;

            $meta = [];
        }

        if (!is_array($meta))
            throw new \Exception(sprintf(
                'Headers must instance of EntityInterface or associated array, "%s" given instead.'
                , is_object($meta) ? get_class($meta) : gettype($meta)
            ));

        $this->headers()->setFrom(new Entity($meta));

        return $this;
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
     * Set Message Body Content
     *
     * @param string $content
     *
     * @return $this
     */
    function setBody($content)
    {
        $this->body = $content;

        return $this;
    }

    /**
     * Get Message Body Content
     *
     * @return string
     */
    function getBody()
    {
        return $this->body;
    }

    /**
     * Render Http Message To String
     *
     * @return string
     */
    function toString()
    {
        $return = '';
        foreach ($this->headers()->keys() as $key)
            $return .= sprintf(
                "%s: %s\r\n",
                (string) $key,
                (string) $this->headers()->get($key)
            );

        $return .= "\r\n" . $this->getBody();

        return $return;
    }

    /**
     * Render Http Message To String
     *
     * @return string
     */
    function __toString()
    {
        return $this->toString();
    }
}
