<?php
namespace Poirot\Http\Interfaces\Message\Request;

use Poirot\Core\Interfaces\EntityInterface;
use Poirot\Core\Interfaces\iPoirotEntity;

interface iHMRPhpServer extends iHMRServer
{
    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return iPoirotEntity
     */
    function get_Server();

    /**
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * ! This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated cookie values.
     *
     * note: This method MUST NOT update the related Cookie header of the request
     * instance, nor related values in the server params.
     *
     * @param array|EntityInterface $params
     *
     * @return $this
     */
    function set_Cookie($params);

    /**
     * Retrieve cookies
     *
     * @return EntityInterface
     */
    function get_Cookie();

    /**
     *
     * ! These values SHOULD remain immutable over the course of the incoming
     * request.
     *
     * ! Setting query string arguments MUST NOT change the URI stored by the
     * request, nor the values in the server params.
     *
     * They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI
     *
     * @param array|EntityInterface $params
     *
     * @return $this
     */
    function set_Get($params);

    /**
     * Retrieve query string arguments
     *
     * Note: the query params might not be in sync with the URI or server
     * params. If you need to ensure you are only getting the original
     * values, you may need to parse the query string from `getUri()->getQuery()`
     * or from the `QUERY_STRING` server param.
     *
     * @return EntityInterface
     */
    function get_Get();

    /**
     * Create a new instance with the specified uploaded files
     *
     * note: This method MUST be implemented in such a way as to retain the
     * immutability of the message
     *
     * @param array $params
     *
     * @return $this
     */
    function set_Files($params);

    /**
     * Retrieve normalized file upload data
     *
     * @return array
     */
    function get_Files();
}
