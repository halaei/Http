<?php
namespace Poirot\Http\HttpMessage;

class PluginsHttpResponse 
    extends PluginsHttp
{
    protected $loader_resources = array(
        'phpserver' => 'Poirot\Http\Plugins\Response\PhpServer',
        'status'    => 'Poirot\Http\Plugins\Response\Status',
    );
}
