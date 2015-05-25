<?php
namespace Poirot\Http\Plugins\Request;

use Poirot\Container\Interfaces\iCService;
use Poirot\Container\Service\AbstractService;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Message\HttpRequest;

class MethodType extends AbstractService
    implements iCService
{
    /**
     * @var string Service Name
     */
    protected $name = 'MethodType'; // default name

    /**
     * Is this an OPTIONS method request?
     *
     * @return bool
     */
    function isOptions()
    {
        return ($this->__getRequestObject()->getMethod() === HttpRequest::METHOD_OPTIONS);
    }

    /**
     * Is this a PROPFIND method request?
     *
     * @return bool
     */
    function isPropFind()
    {
        return ($this->__getRequestObject()->getMethod() === HttpRequest::METHOD_PROPFIND);
    }

    /**
     * Is this a GET method request?
     *
     * @return bool
     */
    function isGet()
    {
        return ($this->__getRequestObject()->getMethod() === HttpRequest::METHOD_GET);
    }

    /**
     * Is this a HEAD method request?
     *
     * @return bool
     */
    function isHead()
    {
        return ($this->__getRequestObject()->getMethod() === HttpRequest::METHOD_HEAD);
    }

    /**
     * Is this a POST method request?
     *
     * @return bool
     */
    function isPost()
    {
        return ($this->__getRequestObject()->getMethod() === HttpRequest::METHOD_POST);
    }

    /**
     * Is this a PUT method request?
     *
     * @return bool
     */
    function isPut()
    {
        return ($this->__getRequestObject()->getMethod() === HttpRequest::METHOD_PUT);
    }

    /**
     * Is this a DELETE method request?
     *
     * @return bool
     */
    function isDelete()
    {
        return ($this->__getRequestObject()->getMethod() === HttpRequest::METHOD_DELETE);
    }

    /**
     * Is this a TRACE method request?
     *
     * @return bool
     */
    function isTrace()
    {
        return ($this->__getRequestObject()->getMethod() === HttpRequest::METHOD_TRACE);
    }

    /**
     * Is this a CONNECT method request?
     *
     * @return bool
     */
    function isConnect()
    {
        return ($this->__getRequestObject()->getMethod() === HttpRequest::METHOD_CONNECT);
    }

    /**
     * Is this a PATCH method request?
     *
     * @return bool
     */
    function isPatch()
    {
        return ($this->__getRequestObject()->getMethod() === HttpRequest::METHOD_PATCH);
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
        $header = $this->__getRequestObject()->getHeaders()->search(['label' => 'X_REQUESTED_WITH']);
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
        $header = $this->__getRequestObject()->getHeaders()->search(['label' => 'USER_AGENT']);
        return false !== $header && stristr($header->getValueString(), ' flash');
    }

    /**
     * @return HttpRequest
     */
    protected function __getRequestObject()
    {
        return $this->getServiceContainer()->getMessageObject();
    }

    // Implement iCService:

    /**
     * Create Service
     *
     * @return mixed
     */
    function createService()
    {
        return $this;
    }
}
