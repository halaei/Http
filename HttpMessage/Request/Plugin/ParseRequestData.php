<?php
namespace Poirot\Http\HttpMessage\Request\Plugin;

use Poirot\Http\HttpMessage\Request\StreamBodyMultiPart;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\Stream\Interfaces\iStreamable;


// TODO register parser by static method globally
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
                $parsedData = $this->_parseJsonDataFromRequest($request);
                break;

            case 'application/x-www-form-urlencoded':
                $parsedData = $this->_parseUrlEncodeDataFromRequest($request);
                break;
            case strpos($contentType, 'multipart') !== false:
                $parsedData = $this->_parseMultipartDataFromRequest($request);
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
    
    
    // ..
    
    protected function _parseJsonDataFromRequest(iHttpRequest $request)
    {
        $parsedData = $request->getBody();
        $parsedData = json_decode($parsedData, true);
        return $parsedData;
    }

    protected function _parseUrlEncodeDataFromRequest(iHttpRequest $request)
    {
        $content = $request->getBody()->read();
        $result  = array();

        parse_str($content, $result);
        return $result;
    }

    protected function _parseMultipartDataFromRequest(iHttpRequest $request)
    {
        $body = $request->getBody();
        if ($body instanceof StreamBodyMultiPart)
            // MultiPart Stream aware of elements included, so just return back
            return $body->listElements();



        # grab multipart boundary from content type header
        # used to parse data
        $header = $request->headers()->get('content-type');
        $contentType = '';
        /** @var iHeader $h */
        foreach ($header as $h)
            $contentType .= $h->renderValueLine();


        preg_match('/boundary=(.*)$/', $contentType, $matches);
        $boundary = $matches[1];


        # render request body content
        $input = $body;

        if ($body instanceof iStreamable)
            $input = $body->read();


        // split content by boundary
        $boundaryBlocks = preg_split("/-+$boundary/", $input);
        array_pop($boundaryBlocks); // get rid of last -- element

        // loop data blocks
        $return = array();
        foreach ($boundaryBlocks as $blockContent)
        {
            if (empty($blockContent))
                continue;


            // parse uploaded files
            if (strpos($blockContent, 'application/octet-stream') !== false) {
                // TODO Implement Feature
                // match "name", then everything after "stream" (optional) except for prepending newlines
                preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $blockContent, $matches);
                $return[$matches[1]] = $matches[2];

                continue;
            }


            // file upload php
            if (strpos($blockContent, 'filename') !== false)
            {
                $headers = \Poirot\Http\Header\parseHeaderLines($blockContent, $offset);

                preg_match('/name=\"([^\"]*)\"; filename=\"([^\"]*)\"/', $headers['Content-Disposition'], $matches);

                $mime     = $headers['Content-Type'];
                $size     = isset($headers['Content-Length']) ? $headers['Content-Length'] : null;
                $name     = $matches[1];
                $filename = $matches[2];
                $content = substr($blockContent, $offset);


                // get current system path and create tempory file name & path
                $path = sys_get_temp_dir().'/phpPoirot'.substr(sha1(rand()), 0, 6);
                // write temporary file to emulate $_FILES super global
                $err = file_put_contents($path, $content);
                register_shutdown_function(function() use ($path) {
                    // delete temporary file when process done
                    unlink($path);
                });

                if (preg_match('/^(.*)\[\]$/i', $name, $tmp))
                    // TODO Did the user use the infamous input name="array[]" for multiple file uploads?
                    VOID;
                
                $fileSpec = array();
                $fileSpec['name']     = $filename;
                $fileSpec['type']     = $mime;
                $fileSpec['tmp_name'] = $path;
                $fileSpec['size']     = ($size) ? $size : strlen($content);
                $fileSpec['error']    = ($err === false) ? UPLOAD_ERR_CANT_WRITE : 0;
                
                $return[$name] = \Poirot\Http\Psr\makeUploadedFileFromSpec($fileSpec); 
                
                continue;
            }


            // parse all other fields

            // match "name" and optional value in between newline sequences
            preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $blockContent, $matches);

            if (preg_match('/^(.*)\[\]$/i', $matches[1], $tmp))
                $return[$tmp[1]][] = $matches[2];
            else
                $return[$matches[1]] = $matches[2];
        }


        return $return;
    }
}
