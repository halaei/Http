<?php
namespace Poirot\Http\Message\Response;

use Poirot\Std\Struct\aDataOptions;

class DataParseResponsePhp
    extends aDataOptions
{
    protected $headers;

    /**
     * @override This is readonly option
     * @inheritdoc
     */
    function __construct()
    {
        return parent::__construct();
    }

    function getStatusCode()
    {
        return \Poirot\Http\Response\httpResponseCode();
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return headers_list();
    }
}
