<?php
namespace Poirot\Http\Message\Request;

use Poirot\Http\Header\CollectionHeader;
use Poirot\Http\Header\FactoryHttpHeader;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHeaders;
use Poirot\Http\Psr\UploadedFile;
use Poirot\Http\Header as UtilHttp;
use Poirot\Stream\Interfaces\iStreamable;
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
    function __construct($multiPart = array(), $boundary = null)
    {
        parent::__construct(new SAggregateStreams());
        
        
        if (!is_array($multiPart) && !is_string($multiPart))
            throw new \InvalidArgumentException(sprintf(
                'The Constructor give array of Files or Raw Body String, given: "%s".'
                , \Poirot\Std\flatten($multiPart)
            ));
        
        if ($boundary === null)
            $boundary = uniqid();
        
        $this->_boundary = (string) $boundary;

        if (is_array($multiPart))
            $this->_fromFilesArray($multiPart);
        else
            $this->_fromRawBodyString($multiPart);
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
     * Build Stream From _FILES or uploadedFiles
     * @param $files
     */
    protected function _fromFilesArray($files)
    {
        if (current($files) instanceof UploadedFileInterface || isset($files['tmp_name']))
            $files = \Poirot\Http\Psr\normalizeFiles($files);

        foreach($files as $field => $file)
            $this->addElement($field, $file);
    }

    /**
     * Append Boundary Element
     *
     * @param string                      $fieldName Form Field Name
     * @param array|UploadedFileInterface $element
     * @param null|CollectionHeader|array $headers   Extra Headers To Be Added
     * 
     * @return $this
     */
    function addElement($fieldName, $element, $headers = null)
    {
        $this->_trailingBoundary = false;

        if ($element instanceof UploadedFileInterface)
            $this->_addUploadedFileElement($fieldName, $element, $headers);
        elseif (is_array($element))
            $this->_addArrayElement($fieldName, $element);
        else
            throw new \InvalidArgumentException(sprintf(
                'Element must be defined array represent element or UploadedFileInterface. given: "%s".'
                , \Poirot\Std\flatten($element)
            ));

        return $this;
    }

    protected function _addUploadedFileElement($fieldName, UploadedFileInterface $element, $headers)
    {
        if (!$headers instanceof iHeaders)
            $headers = ($headers) ? new CollectionHeader($headers) : new CollectionHeader;

        $headers->insert(
            FactoryHttpHeader::of(array(
                'Content-Type'
                , ($type = $element->getClientMediaType()) ? $type : 'application/octet-stream'
            )
        ));

        if ($size = $element->getSize())
            $headers->insert(
                FactoryHttpHeader::of( array('Content-Length', (string) $size) )
            );


        if ($element instanceof UploadedFile)
            ## using poirot stream
            $element->setDefaultStream('\Poirot\Stream\Streamable');


        $this->__createElement($fieldName, $element->getStream(), $element->getClientFilename(), $headers);
    }

    protected function _addArrayElement($element)
    {
        // TODO implement 
    }

    /**
     * @param string                      $name     Form Field Name
     * @param StreamInterface|iStreamable $stream
     * @param string                      $filename File name header
     * @param CollectionHeader|array               $headers  Boundary Headers
     *
     * @return array
     */
    protected function __createElement($name, $stream, $filename, $headers)
    {
        if (is_array($headers))
            $headers = new CollectionHeader($headers);
        elseif (!$headers instanceof CollectionHeader)
            throw new \InvalidArgumentException(sprintf(
                'Headers must be array or Header. given: "%s".'
                , \Poirot\Std\flatten($headers)
            ));

        // Set a default content-disposition header if one was no provided
        if (!$headers->has('content-disposition'))
            $headers->insert(
                FactoryHttpHeader::of(array(
                    'Content-Disposition'
                    , ($filename)
                        ? sprintf('form-data; name="%s"; filename="%s"'
                            , $name
                            , basename($filename)
                        )
                        : "form-data; name=\"{$name}\""
                )
            ));

        // Set a default content-length header if one was no provided
        if (!$headers->has('content-length'))
            (!$length = $stream->getSize())
                ?: $headers->insert(
                    FactoryHttpHeader::of(array(
                        'Content-Length', (string) $length
                    )
                ));


        // Set a default Content-Type if one was not supplied
        if (!$headers->has('content-type') && $filename)
            (!$type = \Poirot\Http\Mime\getFromFilename($filename))
                ?: $headers->insert(FactoryHttpHeader::of( array('Content-Type', $type)) );


        ## Add Created Element As Stream
        ## it included headers and body stream

        ### headers
        $renderHeaders = '';
        /** @var iHeader $first */
        $first = $headers->get('Content-Disposition');
        $renderHeaders .= $first->render()."\r\n";
        ## with new instance on delete
        $headers = $headers->del('Content-Disposition');
        /** @var iHeader $h */
        foreach($headers as $h)
            $renderHeaders .= $h->render()."\r\n";
        $renderHeaders = "--{$this->_boundary}\r\n" . trim($renderHeaders) . "\r\n\r\n";

        $this->_t__wrap_stream->addStream((new STemporary($renderHeaders))->rewind());
        $this->_t__wrap_stream->addStream($stream->rewind());
        $this->_t__wrap_stream->addStream((new STemporary("\r\n"))->rewind());
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
