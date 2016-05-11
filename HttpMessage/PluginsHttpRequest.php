<?php
namespace Poirot\Http\HttpMessage;

class PluginsHttpRequest 
    extends PluginsHttp
{
    protected $loader_resources = array(
        'methodtype' => 'Poirot\Http\Plugins\Request\MethodType',
        'phpserver'  => 'Poirot\Http\Plugins\Request\PhpServer',
    );
}
