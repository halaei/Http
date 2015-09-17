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
            $this->server = new Entity($_SERVER);

        return $this->server;
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
            $this->files = $this->__getFiles();

        return $this->files;
    }

        protected function __getFiles()
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
 