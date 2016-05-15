<?php
namespace Poirot\Http\HttpMessage\Request\Plugin;

use Poirot\Std\Interfaces\Struct\iDataEntity;
use Poirot\Std\Struct\aDataOptions;
use Poirot\Std\Struct\DataEntity;

// TODO

class PhpServer 
    extends aPluginRequest
{
    /** @var iDataEntity $_ENV*/
    protected $env;
    /** @var iDataEntity $_GET*/
    protected $get;
    /** @var iDataEntity $_POST */
    protected $post;
    /** @var iDataEntity $_COOKIE */
    protected $cookie;
    /** @var iDataEntity $_SERVER */
    protected $server;
    /** @var iDataEntity $_FILES */
    protected $files;

    /**
     * Set Env
     * @param null|array|\Traversable $env
     * @return $this
     */
    function setEnv($env)
    {
        $this->env = new DataEntity($env);
        return $this;
    }

    /**
     * Get Env
     * @return DataEntity
     */
    function getEnv()
    {
        if (!$this->env)
            $this->setEnv($_ENV);
        
        return $this->env;
    }

    /**
     * Set Query Get
     * @param null|array|\Traversable $get
     * @return $this
     */
    function setQuery($get)
    {
        $this->get = new DataEntity($get);
        return $this;
    }

    /**
     * Get Query
     * @return DataEntity
     */
    function getQuery()
    {
        if (!$this->get)
            $this->setQuery($_GET);

        return $this->get;
    }

    /**
     * Set Post
     * @param null|array|\Traversable $post
     * @return $this
     */
    function setPost($post)
    {
        $this->post = new DataEntity($post);
        return $this;
    }

    /**
     * Get Post
     * 
     * @param null $key
     * @param null $default
     *
     * @return DataEntity|string value of individual post
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
     * @param null|array|\Traversable $cookie
     * @return $this
     */
    function setCookie($cookie)
    {
        $this->cookie = new DataEntity($cookie);
        return $this;
    }

    /**
     * Get Cookie
     * @return DataEntity
     */
    function getCookie()
    {
        if (!$this->cookie)
            $this->setCookie($_COOKIE);

        return $this->cookie;
    }

    /**
     * Set Server
     * @param null|array|\Traversable $server
     * @return $this
     */
    function setServer($server)
    {
        $this->server = new DataEntity(
            $this->_normalizeServer($server)
        );
        
        return $this->server;
    }

    /**
     * Get Server
     * @return DataEntity
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
        $this->files = \Poirot\Http\Psr\normalizeFiles($files);
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
        $requestUri = $this->getMessageObject()->getUri();

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

    protected function _normalizeServer(array $server)
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
