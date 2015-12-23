<?php
namespace Poirot\Http\Plugins;

class HttpRequestPluginManager extends HttpPluginManager
{
    protected $loader_resources = [
        'methodtype' => 'Poirot\Http\Plugins\Request\MethodType',
        'phpserver'  => 'Poirot\Http\Plugins\Request\PhpServer',
    ];
}
