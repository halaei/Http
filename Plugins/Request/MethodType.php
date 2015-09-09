<?php
namespace Poirot\Http\Plugins\Request;

use Poirot\Container\Interfaces\iCService;
use Poirot\Container\Service\AbstractService;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\Message\iHttpMessage;
use Poirot\Http\Interfaces\Message\iHttpRequest;
use Poirot\Http\Message\HttpRequest;
use Poirot\Http\Plugins\iHttpPlugin;

class MethodType extends AbstractService
    implements iHttpPlugin,
    iCService
{
    /** @var string Service Name */
    protected $name = 'MethodType';

    /** @var iHttpMessage */
    protected $messageObject;

    /**
     * Is this an OPTIONS method request?
     *
     * @return bool
     */
    function isOptions()
    {
        return ($this->getMessageObject()->getMethod() === HttpRequest::METHOD_OPTIONS);
    }

    /**
     * Is this a PROPFIND method request?
     *
     * @return bool
     */
    function isPropFind()
    {
        return ($this->getMessageObject()->getMethod() === HttpRequest::METHOD_PROPFIND);
    }

    /**
     * Is this a GET method request?
     *
     * @return bool
     */
    function isGet()
    {
        return ($this->getMessageObject()->getMethod() === HttpRequest::METHOD_GET);
    }

    /**
     * Is this a HEAD method request?
     *
     * @return bool
     */
    function isHead()
    {
        return ($this->getMessageObject()->getMethod() === HttpRequest::METHOD_HEAD);
    }

    /**
     * Is this a POST method request?
     *
     * @return bool
     */
    function isPost()
    {
        return ($this->getMessageObject()->getMethod() === HttpRequest::METHOD_POST);
    }

    /**
     * Is this a PUT method request?
     *
     * @return bool
     */
    function isPut()
    {
        return ($this->getMessageObject()->getMethod() === HttpRequest::METHOD_PUT);
    }

    /**
     * Is this a DELETE method request?
     *
     * @return bool
     */
    function isDelete()
    {
        return ($this->getMessageObject()->getMethod() === HttpRequest::METHOD_DELETE);
    }

    /**
     * Is this a TRACE method request?
     *
     * @return bool
     */
    function isTrace()
    {
        return ($this->getMessageObject()->getMethod() === HttpRequest::METHOD_TRACE);
    }

    /**
     * Is this a CONNECT method request?
     *
     * @return bool
     */
    function isConnect()
    {
        return ($this->getMessageObject()->getMethod() === HttpRequest::METHOD_CONNECT);
    }

    /**
     * Is this a PATCH method request?
     *
     * @return bool
     */
    function isPatch()
    {
        return ($this->getMessageObject()->getMethod() === HttpRequest::METHOD_PATCH);
    }

    /**
     * Is the request a Javascript XMLHttpRequest?
     *
     * Should work with Prototype/Script.aculo.us, possibly others.
     *
     * @return bool
     */
    function isXmlHttpRequest()
    {
        /** @var iHeader $header */
        $header = $this->getMessageObject()->getHeaders()->search(['label' => 'X_REQUESTED_WITH']);
        return false !== $header && $header->getValueString() == 'XMLHttpRequest';
    }

    /**
     * Is this a Flash request?
     *
     * @return bool
     */
    function isFlashRequest()
    {
        /** @var iHeader $header */
        $header = $this->getMessageObject()->getHeaders()->search(['label' => 'USER_AGENT']);
        return false !== $header && stristr($header->getValueString(), ' flash');
    }


    // Implement iCService

    /**
     * Create Service
     *
     * @return mixed
     */
    function createService()
    {
        return $this;
    }


    // Implement iHttpPlugin

    /**
     * Set Http Message Object (Request|Response)
     *
     * note: so services can have access to http message instance
     *
     * @param iHttpMessage $httpMessage
     *
     * @return $this
     */
    function setMessageObject(iHttpMessage $httpMessage)
    {
        if (!$httpMessage instanceof iHttpRequest)
            throw new \InvalidArgumentException(sprintf(
                'This plugin need request object instance of iHttpRequest, "%s" given.'
                , get_class($httpMessage)
            ));

        $this->messageObject = $httpMessage;

        return $this;
    }

    /**
     * Get Http Message
     *
     * @return iHttpMessage
     */
    function getMessageObject()
    {
        return $this->messageObject;
    }
}
