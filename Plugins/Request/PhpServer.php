<?php
namespace Poirot\Http\Plugins\Request;

use Poirot\Container\Service\AbstractService;
use Poirot\Core\Entity;
use Poirot\Core\Interfaces\iDataSetConveyor;
use Poirot\Http\Plugins\iHttpPlugin;

class PhpServer extends AbstractService
    implements iHttpPlugin
{
    use RequestPluginTrait;

    /** @var string Service Name */
    protected $name = 'PhpServer';

    protected $env;
    protected $get;
    protected $post;
    protected $cookie;
    protected $server;
    protected $files;

    /**
     * Set Env
     * @param array|iDataSetConveyor $env
     * @return $this
     */
    function setEnv($env)
    {
        $this->env = new Entity($env);

        return $this;
    }

    /**
     * Get Env
     * @return Entity
     */
    function getEnv()
    {
        if (!$this->env)
            $this->setEnv($_ENV);

        return $this->env;
    }

    /**
     * Set Query Get
     * @param array|iDataSetConveyor $get
     * @return $this
     */
    function setQuery($get)
    {
        $this->get = new Entity($get);

        return $this;
    }

    /**
     * Get Query
     * @return Entity
     */
    function getQuery()
    {
        if (!$this->get)
            $this->get = new Entity($_GET);

        return $this->get;
    }

    /**
     * Set Post
     * @param array|iDataSetConveyor $post
     * @return $this
     */
    function setPost($post)
    {
        $this->post = new Entity($post);

        return $this;
    }

    /**
     * Get Post
     * @return Entity
     */
    function getPost()
    {
        if (!$this->post)
            $this->setPost($_POST);

        return $this->post;
    }

    /**
     * Set Cookies
     * @param array|iDataSetConveyor $cookie
     * @return $this
     */
    function setCookie($cookie)
    {
        $this->cookie = new Entity($cookie);

        return $this;
    }

    /**
     * Get Cookie
     * @return Entity
     */
    function getCookie()
    {
        if (!$this->cookie)
            $this->setCookie($_COOKIE);

        return $this->cookie;
    }

    /**
     * Set Server
     * @param array|iDataSetConveyor $server
     * @return $this
     */
    function setServer($server)
    {
        $this->server = new Entity($server);

        return $this->server;
    }

    /**
     * Get Server
     * @return Entity
     */
    function getServer()
    {
        if (!$this->server)
            $this->server = new Entity(
                $this->__normalizeServer($_SERVER)
            );

        return $this->server;
    }

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

    /**
     * Set Files
     * @param $files
     * @return $this
     */
    function setFiles($files)
    {
        $this->files = new Entity($files);

        return $this;
    }

    /**
     * Get Files
     * @return Entity
     */
    function getFiles()
    {
        if (!$this->files)
            $this->files = $this->__normalizeFiles();

        return $this->files;
    }

        protected function __normalizeFiles()
        {
            $_F_mapFileParams = function(&$array, $paramName, $index, $value) use (&$_F_mapFileParams) {
                if (!is_array($value))
                    $array[$index][$paramName] = $value;
                else {
                    foreach ($value as $i => $v)
                        $_F_mapFileParams($array[$index], $paramName, $i, $v);
                }
            };

            $files = array();
            foreach ($_FILES as $fileName => $fileParams) {
                $files[$fileName] = array();
                foreach ($fileParams as $param => $data) {
                    if (!is_array($data)) {
                        $files[$fileName][$param] = $data;
                    } else {
                        foreach ($data as $i => $v) {
                            $_F_mapFileParams($files[$fileName], $param, $i, $v);
                        }
                    }
                }
            }

            return new Entity($files);
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
}
 