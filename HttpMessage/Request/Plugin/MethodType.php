<?php
namespace Poirot\Http\HttpMessage\Request\Plugin;

use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHttpMessage;
use Poirot\Http\Interfaces\iHttpRequest;

class MethodType
    extends aPluginRequest
{
    /** @var iHttpMessage */
    protected $messageObject;

    
    /**
     * Is this an OPTIONS method request?
     *
     * @return bool
     */
    function isOptions()
    {
        return ($this->getMessageObject()->getMethod() === iHttpRequest::METHOD_OPTIONS);
    }

    /**
     * Is this a PROPFIND method request?
     *
     * @return bool
     */
    function isPropFind()
    {
        return ($this->getMessageObject()->getMethod() === iHttpRequest::METHOD_PROPFIND);
    }

    /**
     * Is this a GET method request?
     *
     * @return bool
     */
    function isGet()
    {
        return ($this->getMessageObject()->getMethod() === iHttpRequest::METHOD_GET);
    }

    /**
     * Is this a HEAD method request?
     *
     * @return bool
     */
    function isHead()
    {
        return ($this->getMessageObject()->getMethod() === iHttpRequest::METHOD_HEAD);
    }

    /**
     * Is this a POST method request?
     *
     * @return bool
     */
    function isPost()
    {
        return ($this->getMessageObject()->getMethod() === iHttpRequest::METHOD_POST);
    }

    /**
     * Is this a PUT method request?
     *
     * @return bool
     */
    function isPut()
    {
        return ($this->getMessageObject()->getMethod() === iHttpRequest::METHOD_PUT);
    }

    /**
     * Is this a DELETE method request?
     *
     * @return bool
     */
    function isDelete()
    {
        return ($this->getMessageObject()->getMethod() === iHttpRequest::METHOD_DELETE);
    }

    /**
     * Is this a TRACE method request?
     *
     * @return bool
     */
    function isTrace()
    {
        return ($this->getMessageObject()->getMethod() === iHttpRequest::METHOD_TRACE);
    }

    /**
     * Is this a CONNECT method request?
     *
     * @return bool
     */
    function isConnect()
    {
        return ($this->getMessageObject()->getMethod() === iHttpRequest::METHOD_CONNECT);
    }

    /**
     * Is this a PATCH method request?
     *
     * @return bool
     */
    function isPatch()
    {
        return ($this->getMessageObject()->getMethod() === iHttpRequest::METHOD_PATCH);
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
        $header = $this->getMessageObject()->getHeaders()->has('X_REQUESTED_WITH');
        return false !== $header && $header->renderValueLine() == 'XMLHttpRequest';
    }

    /**
     * Is this a Flash request?
     *
     * @return bool
     */
    function isFlashRequest()
    {
        /** @var iHeader $header */
        $header = $this->getMessageObject()->getHeaders()->has('USER_AGENT');
        return false !== $header && stristr($header->renderValueLine(), ' flash');
    }
}
