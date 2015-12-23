<?php
namespace Poirot\Http\Plugins;

class HttpResponsePluginManager extends HttpPluginManager
{
    protected $loader_resources = [
        'phpserver' => 'Poirot\Http\Plugins\Response\PhpServer',
        'status'    => 'Poirot\Http\Plugins\Response\Status',
    ];
}
