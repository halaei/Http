<?php
namespace Poirot\Http\HttpMessage\Request;

use Poirot\Http\Header\CollectionHeader;
use Poirot\Http\Header\FactoryHttpHeader;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHeaders;
use Poirot\Http\Header as UtilHttp;
use Poirot\Psr7\Stream;
use Poirot\Stream\Interfaces\iStreamable;
use Poirot\Stream\Psr\StreamBridgeFromPsr;
use Poirot\Stream\Streamable\SAggregateStreams;
use Poirot\Stream\Streamable\SDecorateStreamable;
use Poirot\Stream\Streamable\STemporary;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;


/**
 * RFC 1867 - Form-based File Upload in HTML
 * @link http://www.faqs.org/rfcs/rfc1867.html
 * TODO multipart/mixed
 */
class StreamBodyMultiPart 
    extends SDecorateStreamable
    implements iStreamable
{
    /** @var SAggregateStreams */
    protected $_t__wrap_stream;

    /** @var string */
    protected $_boundary;
    /** @var false|STemporary Last Trailing Boundary */
    protected $_trailingBoundary;


    /**
     * Construct
     *
     * @param array|string $multiPart _FILES, uploadedFiles, raw body string
     * @param null|string  $boundary
     */
    function __construct($multiPart = null, $boundary = null)
    {
        parent::__construct(new SAggregateStreams);
        
        
        if ($boundary === null)
            $boundary = '----WebKitFormBoundary'.uniqid();
        
        $this->_boundary = (string) $boundary;

        if ($multiPart !== null)
            $this->addElements($multiPart);
    }

    /**
     * Append Boundary Elements
     * 
     * @param array|string $multiPart
     * 
     * @return $this
     */
    function addElements($multiPart)
    {
        if (! (is_array($multiPart) || is_string($multiPart)) )
            throw new \InvalidArgumentException(sprintf(
                'Accept array of Files or Raw Body String, given: "%s".'
                , \Poirot\Std\flatten($multiPart)
            ));
        
        if (is_array($multiPart))
            $this->_fromElementsArray($multiPart);
        else
            $this->_fromRawBodyString($multiPart);
        
        return $this;
    }
    
    /**
     * Append Boundary Element
     *
     * @param string                                       $fieldName Form Field Name
     * @param UploadedFileInterface|string|StreamInterface $element
     * @param null|array                                   $headers   Extra Headers To Be Added
     * 
     * @return $this
     */
    function addElement($fieldName, $element, $headers = null)
    {
        $this->_trailingBoundary = false;

        if (!$headers instanceof iHeaders)
            $headers = ($headers) ? new CollectionHeader($headers) : new CollectionHeader;


        if (is_array($element) && (isset($element['tmp_name']) && isset($element['size'])))
            // Boundary Element is File
            # Convert to UploadedFileInterface
            $element = \Poirot\Http\Psr\makeUploadedFileFromSpec($element);

        
        $headers = clone $headers;

        if ($element instanceof UploadedFileInterface)
            $this->_addUploadedFileElement($fieldName, $element, $headers);
        elseif (\Poirot\Std\isStringify($element) || $element instanceof StreamInterface)
            $this->_addTextElement($fieldName, $element, $headers);
        else
            throw new \InvalidArgumentException(sprintf(
                'Element must be defined array represent element or UploadedFileInterface. given: "%s".'
                , \Poirot\Std\flatten($element)
            ));

        return $this;
    }

    
    // ...

    /**
     * Build Stream From _FILES or uploadedFiles
     * @param $elements
     */
    protected function _fromElementsArray($elements)
    {
        foreach($elements as $name => $element)
            $this->addElement($name, $element);
    }

    /**
     * Build Stream By Parsing Raw Body
     *
     * ! parse string to array that can be used by class
     *
     * @param string $rawBody
     */
    protected function _fromRawBodyString($rawBody)
    {
        // TODO https://gist.github.com/jas-/5c3fdc26fedd11cb9fb5#file-stream-php
    }

    /**
     * @param string                $fieldName
     * @param UploadedFileInterface $element
     * @param CollectionHeader      $headers
     */
    protected function _addUploadedFileElement($fieldName, UploadedFileInterface $element, $headers)
    {
        $headers->insert(FactoryHttpHeader::of(
            array('Content-Type' => ($type = $element->getClientMediaType()) ? $type : 'application/octet-stream')
        ));

        if ($size = $element->getSize())
            $headers->insert(FactoryHttpHeader::of( array('Content-Length' => (string) $size)) );


        // Set a default content-disposition header if one was no provided
        $headers->insert(FactoryHttpHeader::of(
            array( 'Content-Disposition' => sprintf(
                    'form-data; name="%s"; filename="%s"'
                    , $fieldName
                    , basename($element->getClientFilename())
                )
            )
        ));

        // Set a default content-length header if one was no provided
        $headers->insert(FactoryHttpHeader::of(
            array( 'Content-Length' => (string) $element->getStream()->getSize() )
        ));


        // Set a default Content-Type if one was not supplied
        $headers->insert(FactoryHttpHeader::of(
            array( 'Content-Type' => $element->getClientMediaType() )
        ));


        $this->_createElement($fieldName, $element->getStream(), $headers);
    }

    protected function _addTextElement($fieldName, $element, $headers)
    {
        if (!$element instanceof StreamInterface && ! $element instanceof iStreamable)
            $element = new STemporary( (string) $element);

        $this->_createElement($fieldName, $element, $headers);
    }

    /**
     * @param string                      $name     Form Field Name
     * @param StreamInterface|iStreamable $stream
     * @param CollectionHeader            $headers  Boundary Headers
     *
     * @return array
     */
    protected function _createElement($name, $stream, CollectionHeader $headers)
    {
        if (!$headers->has('Content-Disposition'))
            $headers->insert(FactoryHttpHeader::of(
                array( 'Content-Disposition' => "form-data; name=\"{$name}\"" )
            ));


        ## Add Created Element As Stream
        ## it included headers and body stream

        ### headers
        $renderHeaders = '';
        /** @var iHeader $first */
        foreach ($headers->get('Content-Disposition') as $first)
            $renderHeaders .= $first->render()."\r\n";

        ## with new instance on delete
        $headers = $headers->del('Content-Disposition');
        /** @var iHeader $h */
        foreach($headers as $h)
            $renderHeaders .= $h->render()."\r\n";

        $renderHeaders = "--{$this->_boundary}\r\n" . trim($renderHeaders) . "\r\n\r\n";


        $tStream = new STemporary($renderHeaders);
        $this->_t__wrap_stream->addStream($tStream->rewind());

        ($stream instanceof iStreamable) ?: $stream = new StreamBridgeFromPsr($stream);
        $this->_t__wrap_stream->addStream($stream);
        $this->_t__wrap_stream->addStream(new STemporary("\r\n"));
    }

    // ...

    /**
     * Read Data From Stream
     *
     * - if $inByte argument not set, read entire stream
     *
     * @param int $inByte Read Data in byte
     *
     * @throws \Exception Error On Read Data
     * @return string
     */
    function read($inByte = null)
    {
        if (!$this->_trailingBoundary) {
            ## add trailing boundary as stream if not
            $this->_trailingBoundary = new STemporary("--{$this->_boundary}--\r\n");
            $this->_t__wrap_stream->addStream($this->_trailingBoundary->rewind());
        }

        return $this->_t__wrap_stream->read($inByte);
    }

    /**
     * Gets line from stream resource up to a given delimiter
     *
     * Reading ends when length bytes have been read,
     * when the string specified by ending is found
     * (which is not included in the return value),
     * or on EOF (whichever comes first)
     *
     * ! does not return the ending delimiter itself
     *
     * @param string $ending
     * @param int $inByte
     *
     * @return string
     */
    function readLine($ending = "\n", $inByte = null)
    {
        if (!$this->_trailingBoundary) {
            ## add trailing boundary as stream if not
            $this->_trailingBoundary = new STemporary("--{$this->_boundary}--\r\n");
            $this->_t__wrap_stream->addStream($this->_trailingBoundary->rewind());
        }

        return $this->_t__wrap_stream->readLine($ending, $inByte);
    }
}
