<?php
namespace Poirot\Http\HttpMessage\Request\Plugin;

use Poirot\Http\Interfaces\iHeader;
use Poirot\Std\Type\StdTravers;


class ParseRequestData
    extends aPluginRequest
{
    /**
     * Parse Request Body Data
     *
     * @return array
     * @throws \Exception
     */
    function parseBody()
    {
        $request = $this->getMessageObject();

        $header = $request->headers()->get('content-type');
        $contentType = '';
        /** @var iHeader $h */
        foreach ($header as $h)
            $contentType .= $h->renderValueLine();

        $contentType = strtolower($contentType);


        switch ($contentType)
        {
            case 'application/json':
                $parsedData = $request->getBody();
                $parsedData = json_decode($parsedData, true);
                break;

            case 'application/x-www-form-urlencoded':
            case strpos($contentType, 'multipart') !== false:
                $parsedData = PhpServer::_($request)->getPost();
                $parsedData = new StdTravers($parsedData);
                $parsedData = $parsedData->toArray(function ($v) {
                    // not empty fields
                    return empty($v) && $v !== "0";
                }, true);
                break;

            default:
                throw new \Exception(sprintf(
                    'Request Body Contains No Data or Unknown Content-Type (%s).'
                    , $contentType
                ));
        }

        return $parsedData;
    }

    /**
     * Parse Request Query Params
     *
     * @return array
     */
    function parseQueryParams()
    {
        $request = $this->getMessageObject();

        $data = array();
        $url  = $request->getTarget();
        if ($p = parse_url($url, PHP_URL_QUERY))
            parse_str($p, $data);

        return $data;
    }

    /**
     * Parse Request Query Params and Request Body
     *
     * Body Params will override Query Params if Exists
     *
     * @return array
     */
    function parse()
    {
        return array_merge(
            $this->parseQueryParams(),
            $this->parseBody()
        );
    }
}
