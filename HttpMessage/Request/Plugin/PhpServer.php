<?php
namespace Poirot\Http\HttpMessage\Plugins\Request;

use Poirot\Container\Service\AbstractService;
use Poirot\Std\Interfaces\Struct\iDataStruct;
use Poirot\Http\Plugins\iAddOnHttpMessage;
use Poirot\Http\Psr\Util;
use Poirot\Std\Interfaces\Struct\iEntityData;
use Poirot\Std\Struct\EntityData;

class PhpServer extends AbstractService
    implements iAddOnHttpMessage
{
    use RequestPluginTrait;

    /** @var string Service Name */
    protected $name = 'PhpServer';

    protected $env;
    protected $get;
    /** @var iEntityData $_POST */
    protected $post;
    protected $cookie;
    protected $server;
    protected $files;

    /**
     * Set Env
     * @param array|iDataStruct $env
     * @return $this
     */
    function setEnv($env)
    {
        $this->env = new EntityData($env);
        return $this;
    }

    /**
     * Get Env
     * @return EntityData
     */
    function getEnv()
    {
        if (!$this->env)
            $this->setEnv($_ENV);

        return $this->env;
    }

    /**
     * Set Query Get
     * @param array|iDataStruct $get
     * @return $this
     */
    function setQuery($get)
    {
        $this->get = new EntityData($get);
        return $this;
    }

    /**
     * Get Query
     * @return EntityData
     */
    function getQuery()
    {
        if (!$this->get)
            $this->setQuery($_GET);

        return $this->get;
    }

    /**
     * Set Post
     * @param array|iDataStruct $post
     * @return $this
     */
    function setPost($post)
    {
        $this->post = new EntityData($post);
        return $this;
    }

    /**
     * Get Post
     * @param null $key
     * @param null $default
     *
     * @return EntityData|mixed
     */
    function getPost($key = null, $default = null)
    {
        if (!$this->post)
            $this->setPost($_POST);

        if ($key !== null)
            return $this->post->get($key, $default);

        return $this->post;
    }

    /**
     * Set Cookies
     * @param array|iDataStruct $cookie
     * @return $this
     */
    function setCookie($cookie)
    {
        $this->cookie = new EntityData($cookie);
        return $this;
    }

    /**
     * Get Cookie
     * @return EntityData
     */
    function getCookie()
    {
        if (!$this->cookie)
            $this->setCookie($_COOKIE);

        return $this->cookie;
    }

    /**
     * Set Server
     * @param array|iDataStruct $server
     * @return $this
     */
    function setServer($server)
    {
        $this->server = new EntityData(
            $this->__normalizeServer($server)
        );
        return $this->server;
    }

    /**
     * Get Server
     * @return EntityData
     */
    function getServer()
    {
        if (!$this->server)
            $this->setServer($_SERVER);

        return $this->server;
    }

    /**
     * Set Files
     * @param array $files
     * @return $this
     */
    function setFiles($files)
    {
        $this->files = Util::normalizeFiles($files);
        return $this;
    }

    /**
     * Get Files
     * @return array[UploadedFiles]
     */
    function getFiles()
    {
        if (!$this->files)
            // TODO from body looking for multipart/form-data when using as plugin
            $this->setFiles($_FILES);

        return $this->files;
    }

    /**
     * Detect Base Url
     *
     * TODO refactor
     *
     * @throws \Exception
     * @return string
     */
    function getBaseUrl()
    {
        $baseUrl        = '';

        $filename       = $this->getServer()->get('SCRIPT_FILENAME', '');
        $scriptName     = $this->getServer()->get('SCRIPT_NAME');
        $phpSelf        = $this->getServer()->get('PHP_SELF');
        $origScriptName = $this->getServer()->get('ORIG_SCRIPT_NAME', false);

        if ($scriptName !== null && basename($scriptName) === $filename) {
            $baseUrl = $scriptName;
        } elseif ($phpSelf !== null && basename($phpSelf) === $filename) {
            $baseUrl = $phpSelf;
        } elseif ($origScriptName !== null && basename($origScriptName) === $filename) {
            // 1and1 shared hosting compatibility.
            $baseUrl = $origScriptName;
        } else {
            // Backtrack up the SCRIPT_FILENAME to find the portion
            // matching PHP_SELF.

            $baseUrl  = '/';
            $basename = basename($filename);
            if ($basename) {
                $path     = ($phpSelf ? trim($phpSelf, '/') : '');
                $baseUrl .= substr($path, 0, strpos($path, $basename)) . $basename;
            }
        }

        // Does the base URL have anything in common with the request URI?
        $requestUri = $this->getMessageObject()->getUri()->getPath();
        $requestUri = $requestUri->toString();

        // Full base URL matches.
        if (0 === strpos($requestUri, $baseUrl))
            return $baseUrl;

        // Directory portion of base path matches.
        $baseDir = str_replace('\\', '/', dirname($baseUrl));
        if (0 === strpos($requestUri, $baseDir))
            return $baseDir;

        $truncatedRequestUri = $requestUri;

        if (false !== ($pos = strpos($requestUri, '?')))
            $truncatedRequestUri = substr($requestUri, 0, $pos);

        $basename = basename($baseUrl);

        // No match whatsoever
        if (empty($basename) || false === strpos($truncatedRequestUri, $basename))
            return '';

        // If using mod_rewrite or ISAPI_Rewrite strip the script filename
        // out of the base path. $pos !== 0 makes sure it is not matching a
        // value from PATH_INFO or QUERY_STRING.
        if (strlen($requestUri) >= strlen($baseUrl)
            && (false !== ($pos = strpos($requestUri, $baseUrl)) && $pos !== 0)
        ) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return $baseUrl;
    }

    /**
     * Detect Base Path
     *
     * TODO refactor
     *
     * @throws \Exception
     * @return string
     */
    function getBasePath()
    {
        $filename = basename($this->getServer()->get('SCRIPT_FILENAME', ''));
        $baseUrl  = $this->getBaseUrl();

        // Empty base url detected
        if ($baseUrl === '')
            return '';

        // basename() matches the script filename; return the directory
        if (basename($baseUrl) === $filename)
            return str_replace('\\', '/', dirname($baseUrl));

        // Base path is identical to base URL
        return rtrim($baseUrl, '/');
    }

    // ...

    /**
     * Create Service
     *
     * @return mixed
     */
    function createService()
    {
        return $this;
    }


    // ...

    protected function __normalizeServer(array $server)
    {
        if (is_callable('apache_request_headers')) {
            $apacheHeaders = apache_request_headers();
            if (isset($apacheHeaders['Authorization']))
                $server['HTTP_AUTHORIZATION'] = $apacheHeaders['Authorization'];
            elseif (isset($apacheHeaders['authorization']))
                $server['HTTP_AUTHORIZATION'] = $apacheHeaders['authorization'];
        }

        if (isset($server['CONTENT_TYPE']))
            $server['HTTP_CONTENT_TYPE'] = $server['CONTENT_TYPE'];
        if (isset($server['CONTENT_LENGTH']))
            $server['HTTP_CONTENT_LENGTH'] = $server['CONTENT_LENGTH'];

        return $server;
    }
}
