<?php
namespace Poirot\Http\HttpMessage\Request\Plugin;

use Poirot\Http\Interfaces\iHeader;


class ParseRequestBody
    extends aPluginRequest
{
    /**
     * Parse Request Body Data
     *
     * @return array
     * @throws \Exception
     */
    function parseData()
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
                break;

            default:
                throw new \Exception(sprintf(
                    'Request Body Contains No Data or Unknown Content-Type (%s).'
                    , $contentType
                ));
        }

        return $parsedData;
    }
}
